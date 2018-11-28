<?php

/*
 * This file is part of the Sonata Project package.
 *
 * (c) Thomas Rabaix <thomas.rabaix@sonata-project.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Splash\Admin\Model;

use Doctrine\Common\Util\ClassUtils;
use Doctrine\DBAL\DBALException;
use Doctrine\DBAL\LockMode;
use Doctrine\DBAL\Types\Type;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\Query;
use Doctrine\ORM\QueryBuilder;
use Doctrine\ORM\UnitOfWork;
use Exporter\Source\DoctrineORMQuerySourceIterator;
use Sonata\AdminBundle\Admin\FieldDescriptionInterface;
use Sonata\AdminBundle\Datagrid\DatagridInterface;
use Sonata\AdminBundle\Datagrid\ProxyQueryInterface;
use Sonata\AdminBundle\Exception\LockException;
use Sonata\AdminBundle\Exception\ModelManagerException;
use Sonata\AdminBundle\Model\LockInterface;
use Sonata\AdminBundle\Model\ModelManagerInterface;
use Sonata\DoctrineORMAdminBundle\Admin\FieldDescription;
use Sonata\DoctrineORMAdminBundle\Datagrid\OrderByToSelectWalker;
use Sonata\DoctrineORMAdminBundle\Datagrid\ProxyQuery;
use Symfony\Bridge\Doctrine\RegistryInterface;

use Symfony\Component\Form\Exception\PropertyAccessDeniedException;

use ArrayObject;

use Splash\Core\SplashCore as Splash;
use Splash\Components\FieldsManager;

use Splash\Bundle\Services\ConnectorsManager;
//use Doctrine\ORM\Mapping\ClassMetadata;
use Splash\Bundle\Models\ConnectorInterface;


class ObjectsManager implements ModelManagerInterface, LockInterface
{
    /**
     * @var ConnectorsManager
     */
    private $Manager;    
    
    /**
     * @var EntityManager
     */
    private $Entitymanager;

    /**
     * @var string
     */
    private $ServerId;
    
    /**
     * Current Splash Connector Service
     * @var ConnectorInterface
     */
    private $Connector;
        
    /**
     * @var string
     */
    private $ObjectType = null;

    /**
     * @abstract    Fields Cache
     * @var array
     */
    private $Fields     = array();
    
    const ID_SEPARATOR = '~';
    
    /**
     * @var RegistryInterface
     */
    protected $registry;

    /**
     * @var EntityManager[]
     */
    protected $cache = [];
    
    public function __construct(ConnectorsManager $Manager, EntityManager $EntityManager)
    {
        $this->Manager      =   $Manager;
        $this->Entitymanager=   $EntityManager;
    }

    //====================================================================//
    // SPLASH Model Manager Functions
    //====================================================================//

    /**
     * @abstract    Setup Current Splash Object Type
     * @param   string  $ObjectType
     * @return  $this
     */
    public function setObjectType(string $ObjectType)
    {
        $this->ObjectType   =   $ObjectType;
        return $this;
    } 

    /**
     * @abstract    Select Splash Bundle Connection to Use
     * @param   string   $ServerId
     * @return  $this
     */
    public function setServerId(string $ServerId) 
    {
        $this->ServerId    =   $ServerId;
        return $this;
    }    

    /**
     * @abstract    Get Splash Bundle Connection Server Id
     * @return  string
     */
    public function getServerId() : string 
    {
        return $this->ServerId;
    }    

    
    /**
     * @abstract    Get Current Splash Connetor
     * @return      ConnectorInterface
     */
    public function getConnector() 
    {
        //====================================================================//
        // Load Connector with DataBase Config if Exists
        $this->Connector    =   $this->Manager->get(
                $this->ServerId,
                $this->getDataBaseConfiguration()
                );
        //====================================================================//
        // Setup Server as Current Server
        $this->Manager->identify($this->Manager->getWebserviceId($this->ServerId));
        Splash::reboot();
        //====================================================================//
        // Safety Check
        if (!$this->Connector) {
            throw new \RuntimeException('Unable to Identify linked Connector');
        }        
        return $this->Connector;
    }
    
    /**
     * @abstract    Get Server Stored Configuration
     * @return      array
     */
    public function getDataBaseConfiguration() 
    {
        //====================================================================//
        // Load Configuration from DataBase if Exists
        $DbConfig   = $this->Entitymanager->getRepository("SplashAdminBundle:SplashServer")->findOneByIdentifier($this->ServerId);
        //====================================================================//
        // Return Configuration
        if (empty($DbConfig)) {
            return  array();
        }
        return $DbConfig->getSettings();
    }    
    
    /**
     * @abstract    Get Current Connector Service Configuration
     * @return      array
     */
    public function getConfiguration() 
    {
        return $this->getConnector()->getConfiguration();
    } 
    
    /**
     * @abstract    Fetch Connector Available Objects Types
     * 
     * @return     ArrayObject|bool
     */    
    public function getObjects()
    {
        //====================================================================//
        // Read Objects Type List        
        return $this->getConnector()->getAvailableObjects(
            $this->getConfiguration()
        );
    }
    
    /**
     * @abstract    Fetch Connector Available Objects List 
     * 
     * @return     ArrayObject|bool
     */    
    public function getObjectsDefinition()
    {
        //====================================================================//
        // Read Objects Type List        
        $ObjectTypes =  $this->getConnector()->getAvailableObjects();
        //====================================================================//
        // Read Description of All Objects        
        $Objects    =   array();
        foreach ($ObjectTypes as $ObjectType) {
            $Objects[$ObjectType]   =   $this->getConnector()->getObjectDescription(
                $ObjectType
            );
        }
        return $Objects;
    }
    
    /**
     * @param string $class
     *
     * @return ClassMetadata
     */
    public function getObjectFields()
    {
        if (!isset($this->Fields[$this->ObjectType])) {
            $this->Fields[$this->ObjectType]    =   $this->getConnector()->getObjectFields($this->ObjectType);
        }
        return $this->Fields[$this->ObjectType];
    }
    
    /**
     * @param string $class
     *
     * @return ClassMetadata
     */
    public function getMetadata($class)
    {
        return new ClassMetadata("ArrayObject");
    }

    /**
     * @param string $class
     * @return bool
     */
    public function hasMetadata($class)
    {
        return false;
    }

    public function getNewFieldDescriptionInstance($class, $name, array $options = [])
    {
        if (!is_string($name)) {
            throw new \RuntimeException('The name argument must be a string');
        }
        $fieldDescription = new FieldDescription();
        $fieldDescription->setName($name);
        $fieldDescription->setOptions($options);
        return $fieldDescription;
    }

    public function create($object)
    {       
        unset($object->id);
        try {
            //====================================================================//
            // Write Object Data      
            $object->id  =   $this->getConnector()
                    ->setObject($this->ObjectType, null, $object->getArrayCopy());   
        } catch (\PDOException $e) {
            throw new ModelManagerException(
                sprintf('Failed to create object: %s', ClassUtils::getClass($object)),
                $e->getCode(),
                $e
            );
        } catch (DBALException $e) {
            throw new ModelManagerException(
                sprintf('Failed to create object: %s', ClassUtils::getClass($object)),
                $e->getCode(),
                $e
            );
        }
        //====================================================================//
        // Catch Splash Logs 
        $this->Manager->pushLogToSession();
    }

    public function update($object)
    {
        //====================================================================//
        // Safety Check - Verify Object has Id      
        if (empty($object->id)) {
            return;
        }
        //====================================================================//
        // Execute Reverse Transform      
        $ObjectId       = $object->id;
        $ObjectData     = $this->modelReverseTransform("ArrayObject", $object->getArrayCopy());
        //====================================================================//
        // Do Object Update     
        try {
            //====================================================================//
            // Write Object Data      
            $this->getConnector()
                    ->setObject($this->ObjectType, $ObjectId, $ObjectData);        
        } catch (\PDOException $e) {
            throw new ModelManagerException(
                sprintf('Failed to update object: %s', ClassUtils::getClass($object)),
                $e->getCode(),
                $e
            );
        } catch (DBALException $e) {
            throw new ModelManagerException(
                sprintf('Failed to update object: %s', ClassUtils::getClass($object)),
                $e->getCode(),
                $e
            );
        }
        //====================================================================//
        // Catch Splash Logs 
        $this->Manager->pushLogToSession();
    }
    
    public function delete($object)
    {
        try {
            //====================================================================//
            // Delete Object Data      
            $this->getConnector()
                    ->deleteObject($this->ObjectType, $object->id);        
        } catch (\PDOException $e) {
            throw new ModelManagerException(
                sprintf('Failed to delete object: %s', ClassUtils::getClass($object)),
                $e->getCode(),
                $e
            );
        } catch (DBALException $e) {
            throw new ModelManagerException(
                sprintf('Failed to delete object: %s', ClassUtils::getClass($object)),
                $e->getCode(),
                $e
            );
        }
        //====================================================================//
        // Catch Splash Logs 
        $this->Manager->pushLogToSession();
    }

    public function getLockVersion($object)
    {
        $metadata = $this->getMetadata(ClassUtils::getClass($object));

        if (!$metadata->isVersioned) {
            return;
        }

        return $metadata->reflFields[$metadata->versionField]->getValue($object);
    }

    public function lock($object, $expectedVersion)
    {    
    }

    public function find($class, $Id)
    {
        if (!isset($Id)) {
            return;
        }
        //====================================================================//
        // Prepare Readable Fields List
        $Fields = FieldsManager::reduceFieldList(
                $this->getObjectFields(), 
                true
            );
        //====================================================================//
        // Read Object Data      
        $Object =   $this->getConnector()->getObject($this->ObjectType, $Id, $Fields);
        //====================================================================//
        // Return Object      
        return $this->modelTransform("ArrayObject", $Object);
    }

    public function findBy($class, array $criteria = [])
    {
        return $this->getConnector()->getObjectList($class);
    }

    /**
     * {@inheritdoc}
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function modelTransform($class, $instance)
    {
        //====================================================================//
        // Detect Empty Lists      
        foreach ($this->getObjectFields() as $Field) {
            // Only for Writable Fields
            if (empty($Field->write)) {
                continue;
            }             
            if (!FieldsManager::isListField($Field->type)) {
                continue;
            }
            $Listname   =   FieldsManager::isListField($Field->id)['listname'];
            if(empty($instance[$Listname])) {
                $instance[$Listname] = array();
            }
        }
        //====================================================================//
        // Prepare Writable Fields List
        $WriteFields = FieldsManager::reduceFieldList(
                $this->getObjectFields(), 
                true,
                true
            );
        //====================================================================//
        // Remove Read Only Fields
        $Filtered   =   FieldsManager::filterData($instance, array_merge(["id"], $WriteFields));
        //====================================================================//
        // Return Object      
        return new ArrayObject($Filtered, ArrayObject::ARRAY_AS_PROPS);
    }
    
    /**
     * {@inheritdoc}
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function modelReverseTransform($class, array $array = [])
    {
        unset($array["id"]);
        return $array;
    }
    
    //====================================================================//
    // Unused Original Model Manager Functions
    //====================================================================//
    
    /**
     * {@inheritdoc}
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function findOneBy($class, array $criteria = [])
    {
        return array();
    }
    
    /**
     * {@inheritdoc}
     */
    public function getParentFieldDescription($parentAssociationMapping, $class)
    {
        $fieldName = $parentAssociationMapping['fieldName'];
        $metadata = $this->getMetadata($class);
        $associatingMapping = $metadata->associationMappings[$parentAssociationMapping];
        $fieldDescription = $this->getNewFieldDescriptionInstance($class, $fieldName);
        $fieldDescription->setName($parentAssociationMapping);
        $fieldDescription->setAssociationMapping($associatingMapping);
        return $fieldDescription;
    }

    /**
     * {@inheritdoc}
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function createQuery($class, $alias = 'o')
    {
        return new ProxyQuery();
    }

    public function executeQuery($query)
    {
        if ($query instanceof QueryBuilder) {
            return $query->getQuery()->execute();
        }
        return $query->execute();
    }

    /**
     * {@inheritdoc}
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function getModelIdentifier($class)
    {
        return ["id"];
    }

    /**
     * {@inheritdoc}
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function getIdentifierValues($entity)
    {
        return array();
    }

    /**
     * {@inheritdoc}
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function getIdentifierFieldNames($class)
    {
        return $this->getMetadata($class)->getIdentifierFieldNames();
    }

    /**
     * {@inheritdoc}
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function getNormalizedIdentifier($entity)
    {
        return $entity["id"];
    }

    /**
     * {@inheritdoc}
     *
     * The ORM implementation does nothing special but you still should use
     * this method when using the id in a URL to allow for future improvements.
     */
    public function getUrlsafeIdentifier($entity)
    {
        return $this->getNormalizedIdentifier($entity);
    }

    /**
     * {@inheritdoc}
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function addIdentifiersToQuery($class, ProxyQueryInterface $queryProxy, array $idx)
    {
    }

    /**
     * {@inheritdoc}
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function batchDelete($class, ProxyQueryInterface $queryProxy)
    {
    }

    /**
     * {@inheritdoc}
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function getDataSourceIterator(DatagridInterface $datagrid, array $fields, $firstResult = null, $maxResult = null)
    {
    }

    /**
     * {@inheritdoc}
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function getExportFields($class)
    {
        $metadata = $this->getEntityManager($class)->getClassMetadata($class);
        return $metadata->getFieldNames();
    }

    /**
     * {@inheritdoc}
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function getModelInstance($class)
    {
        return new ArrayObject(array(), ArrayObject::ARRAY_AS_PROPS);
    }

    /**
     * {@inheritdoc}
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function getSortParameters(FieldDescriptionInterface $fieldDescription, DatagridInterface $datagrid)
    {
    }

    /**
     * {@inheritdoc}
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function getPaginationParameters(DatagridInterface $datagrid, $page)
    {
        $values = $datagrid->getValues();

        $values['_sort_by'] = $values['_sort_by']->getName();
        $values['_page'] = $page;

        return ['filter' => $values];
    }

    /**
     * {@inheritdoc}
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function getDefaultSortValues($class)
    {
        return [
            '_sort_order' => 'ASC',
            '_sort_by' => implode(',', $this->getModelIdentifier($class)),
            '_page' => 1,
            '_per_page' => 25,
        ];
    }

    /**
     * {@inheritdoc}
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function getModelCollectionInstance($class)
    {
        return new \Doctrine\Common\Collections\ArrayCollection();
    }

    /**
     * {@inheritdoc}
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function collectionClear(&$collection)
    {
        return $collection->clear();
    }

    /**
     * {@inheritdoc}
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function collectionHasElement(&$collection, &$element)
    {
        return $collection->contains($element);
    }

    /**
     * {@inheritdoc}
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function collectionAddElement(&$collection, &$element)
    {
        return $collection->add($element);
    }

    /**
     * {@inheritdoc}
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function collectionRemoveElement(&$collection, &$element)
    {
        return $collection->removeElement($element);
    }

    /**
     * method taken from Symfony\Component\PropertyAccess\PropertyAccessor.
     *
     * @param string $property
     *
     * @return mixed
     */
    protected function camelize($property)
    {
        return str_replace(' ', '', ucwords(str_replace('_', ' ', $property)));
    }
    
}
