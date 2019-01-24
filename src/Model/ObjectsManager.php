<?php

/*
 *  This file is part of SplashSync Project.
 *
 *  Copyright (C) 2015-2019 Splash Sync  <www.splashsync.com>
 *
 *  This program is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 *
 *  For the full copyright and license information, please view the LICENSE
 *  file that was distributed with this source code.
 */

namespace Splash\Admin\Model;

use ArrayObject;
use Doctrine\Common\Util\ClassUtils;
use Doctrine\DBAL\DBALException;
use Doctrine\DBAL\Types\Type;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\QueryBuilder;
use Sonata\AdminBundle\Admin\FieldDescriptionInterface;
use Sonata\AdminBundle\Datagrid\DatagridInterface;
use Sonata\AdminBundle\Datagrid\ProxyQueryInterface;
use Sonata\AdminBundle\Exception\ModelManagerException;
use Sonata\AdminBundle\Model\LockInterface;
use Sonata\AdminBundle\Model\ModelManagerInterface;
use Sonata\DoctrineORMAdminBundle\Admin\FieldDescription;
use Sonata\DoctrineORMAdminBundle\Datagrid\ProxyQuery;
use Splash\Bundle\Events\ObjectsIdChangedEvent;
use Splash\Bundle\Models\AbstractConnector;
use Splash\Bundle\Services\ConnectorsManager;
use Splash\Components\FieldsManager;
use Splash\Core\SplashCore as Splash;
use Symfony\Bridge\Doctrine\RegistryInterface;

/**
 * @abstract Splas Objects Model Manager for Soinata Admin
 *
 * @SuppressWarnings(PHPMD.ExcessivePublicCount)
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class ObjectsManager implements ModelManagerInterface, LockInterface
{
    const ID_SEPARATOR = '~';

    /**
     * @var RegistryInterface
     */
    protected $registry;

    /**
     * @var EntityManager[]
     */
    protected $cache = array();
    
    /**
     * @var ConnectorsManager
     */
    private $manager;

    /**
     * @var EntityManager
     */
    private $entityManager;

    /**
     * @var string
     */
    private $serverId;

    /**
     * @var bool
     */
    private $isShowMode = false;
    
    /**
     * Current Splash Connector Service.
     *
     * @var AbstractConnector
     */
    private $connector;

    /**
     * @var string
     */
    private $objectType;

    /**
     * Fields Cache
     *
     * @var array
     */
    private $fields = array();

    /**
     * Store New Object Id when Changed during Edit
     *
     * @var string
     */
    private $newObjectId;
    
    /**
     * Class Constructor
     *
     * @param ConnectorsManager $manager
     * @param EntityManager     $entityManager
     */
    public function __construct(ConnectorsManager $manager, EntityManager $entityManager)
    {
        $this->manager = $manager;
        $this->entityManager = $entityManager;
    }

    //====================================================================//
    // SPLASH Model Manager Functions
    //====================================================================//

    /**
     * Setup Current Splash Object Type
     *
     * @param string $objectType
     *
     * @return $this
     */
    public function setObjectType(string $objectType)
    {
        $this->objectType = $objectType;

        return $this;
    }

    /**
     * Select Splash Bundle Connection to Use
     *
     * @param string $serverId
     *
     * @return $this
     */
    public function setServerId(string $serverId)
    {
        $this->serverId = $serverId;

        return $this;
    }

    /**
     * Select Show Mode to Allow reading of ReadOnly Fields
     *
     * @return $this
     */
    public function setShowMode()
    {
        $this->isShowMode = true;

        return $this;
    }
    
    /**
     * Get Splash Bundle Connection Server Id
     *
     * @return string
     */
    public function getServerId(): string
    {
        return $this->serverId;
    }

    /**
     * Get Current Splash Connetor
     *
     * @return AbstractConnector
     */
    public function getConnector()
    {
        //====================================================================//
        // Load Connector with DataBase Config if Exists
        $connector = $this->manager->get($this->serverId);
        //====================================================================//
        // Setup Server as Current Server
        $this->manager->identify((string) $this->manager->getWebserviceId($this->serverId));
        Splash::reboot();
        //====================================================================//
        // Safety Check
        if (is_null($connector)) {
            throw new \RuntimeException('Unable to Identify linked Connector');
        }

        return $this->connector = $connector;
    }

    /**
     * Get Current Connector Service Configuration
     *
     * @return array
     */
    public function getConfiguration()
    {
        return $this->getConnector()->getConfiguration();
    }

    /**
     * Fetch Connector Available Objects Types
     *
     * @return array
     */
    public function getObjects()
    {
        //====================================================================//
        // Read Objects Type List
        return $this->getConnector()->getAvailableObjects();
    }

    /**
     * Detect Object Id was Changed so that we could redirect to New Id Page
     *
     * @param ObjectsIdChangedEvent $event
     *
     * @return void
     */
    public function onObjectIdChangedEvent(ObjectsIdChangedEvent $event) : void
    {
        //====================================================================//
        // Store New Object Id
        $this->newObjectId = $event->getNewObjectId();
    }
    
    /**
     * If Object Id was Changed, Return New Object Id so that we could redirect to New Id Page
     *
     * @return null|string
     */
    public function getNewObjectId()
    {
        return isset($this->newObjectId) ? $this->newObjectId : null;
    }
    
    /**
     * Fetch Connector Available Objects List
     *
     * @return array
     */
    public function getObjectsDefinition()
    {
        //====================================================================//
        // Read Objects Type List
        $objectTypes = $this->getConnector()->getAvailableObjects();
        //====================================================================//
        // Read Description of All Objects
        $objects = array();
        foreach ($objectTypes as $objectType) {
            $objects[$objectType] = $this->getConnector()->getObjectDescription(
                $objectType
            );
        }

        return $objects;
    }

    /**
     * Get Object Fields Array
     *
     * @return array[ArrayObject]
     */
    public function getObjectFields()
    {
        if (!isset($this->fields[$this->objectType])) {
            $this->fields[$this->objectType] = $this->getConnector()->getObjectFields($this->objectType);
        }

        return $this->fields[$this->objectType];
    }

    /**
     * {@inheritdoc}
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function getMetadata($class)
    {
        return new ClassMetadata('ArrayObject');
    }

    /**
     * {@inheritdoc}
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function hasMetadata($class)
    {
        return false;
    }

    /**
     * {@inheritdoc}
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function getNewFieldDescriptionInstance($class, $name, array $options = array())
    {
        if (!is_string($name)) {
            throw new \RuntimeException('The name argument must be a string');
        }
        $fieldDescription = new FieldDescription();
        $fieldDescription->setName($name);
        $fieldDescription->setOptions($options);

        return $fieldDescription;
    }

    /**
     * Create A New Object via Connector
     *
     * @param ArrayObject $object
     *
     * @throws ModelManagerException
     */
    public function create($object)
    {
        $object->id = null;

        try {
            //====================================================================//
            // Write Object Data
            $object->id = $this->getConnector()
                ->setObject($this->objectType, null, $object->getArrayCopy());
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
        $this->manager->pushLogToSession(true);

        //====================================================================//
        // Return New Object
        return $object;
    }

    /**
     * Update Object via Connector
     *
     * @param ArrayObject $object
     *
     * @throws ModelManagerException
     */
    public function update($object)
    {
        //====================================================================//
        // Safety Check - Verify Object has Id
        if (empty($object->id) || !($object instanceof ArrayObject)) {
            return;
        }
        //====================================================================//
        // Execute Reverse Transform
        $objectId = $object->id;
        $objectData = $this->modelReverseTransform('ArrayObject', $object->getArrayCopy());
        //====================================================================//
        // Do Object Update
        try {
            //====================================================================//
            // Write Object Data
            $this->getConnector()
                ->setObject($this->objectType, $objectId, $objectData);
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
        $this->manager->pushLogToSession(true);
    }

    /**
     * Delete Object via Connector
     *
     * @param ArrayObject $object
     *
     * @throws ModelManagerException
     */
    public function delete($object)
    {
        try {
            //====================================================================//
            // Delete Object Data
            $this->getConnector()
                ->deleteObject($this->objectType, $object->id);
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
        $this->manager->pushLogToSession(true);
    }

    /**
     * {@inheritdoc}
     */
    public function getLockVersion($object)
    {
        $metadata = $this->getMetadata(ClassUtils::getClass($object));

        if (!$metadata->isVersioned) {
            return;
        }

        return $metadata->reflFields[$metadata->versionField]->getValue($object);
    }

    /**
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     *
     * @param mixed $object
     * @param mixed $expectedVersion
     */
    public function lock($object, $expectedVersion)
    {
    }

    /**
     *
     * @param mixed $class
     * @param mixed $objectId
     *
     * @return ArrayObject|false
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function find($class, $objectId)
    {
        if (!isset($objectId)) {
            return false;
        }
        //====================================================================//
        // Prepare Readable Fields List
        $fields = FieldsManager::reduceFieldList(
            $this->getObjectFields(),
            true
        );
        //====================================================================//
        // Read Object Data
        $object = $this->getConnector()->getObject($this->objectType, $objectId, $fields);
        if (empty($object)) {
            return false;
        }
        //====================================================================//
        // Return Object
        return $this->modelTransform('ArrayObject', $object);
    }

    /**
     * {@inheritdoc}
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function findBy($class, array $criteria = array())
    {
        return $this->getConnector()->getObjectList($class);
    }

    /**
     * @param string $class
     * @param array  $instance
     *
     * @return ArrayObject
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function modelTransform($class, $instance)
    {
        //====================================================================//
        // Detect Empty Lists
        /** @var ArrayObject $field */
        foreach ($this->getObjectFields() as $field) {
            // Only for Writable Fields
            if (!$this->isShowMode && empty($field->write)) {
                continue;
            }
            if (!FieldsManager::isListField($field->type)) {
                continue;
            }
            $listName = FieldsManager::isListField($field->id)['listname'];
            if (empty($instance[$listName])) {
                $instance[$listName] = array();
            }
        }
        //====================================================================//
        // Prepare Writable Fields List
        $writeFields = FieldsManager::reduceFieldList(
            $this->getObjectFields(),
            true,
            !$this->isShowMode
        );
        //====================================================================//
        // Remove Read Only Fields
        $filtered = FieldsManager::filterData($instance, array_merge(array('id'), $writeFields));
        if (is_null($filtered)) {
            return new ArrayObject(array(), ArrayObject::ARRAY_AS_PROPS);
        }
        //====================================================================//
        // Return Object
        return new ArrayObject($filtered, ArrayObject::ARRAY_AS_PROPS);
    }

    /**
     * {@inheritdoc}
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function modelReverseTransform($class, array $array = array())
    {
        unset($array['id']);

        return $array;
    }

    //====================================================================//
    // Unused Original Model Manager Functions
    //====================================================================//

    /**
     * {@inheritdoc}
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function findOneBy($class, array $criteria = array())
    {
        return new ArrayObject();
    }

    /**
     * {@inheritdoc}
     */
    public function getParentFieldDescription($parentAssoMapping, $class)
    {
        $fieldName = $parentAssoMapping['fieldName'];
        $metadata = $this->getMetadata($class);
        $associatingMapping = $metadata->associationMappings[$fieldName];
        $fieldDescription = $this->getNewFieldDescriptionInstance($class, $fieldName);
        $fieldDescription->setName($parentAssoMapping);
        $fieldDescription->setAssociationMapping($associatingMapping);

        return $fieldDescription;
    }

    /**
     * {@inheritdoc}
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function createQuery($class, $alias = 'o')
    {
        return new ProxyQuery(new QueryBuilder($this->entityManager));
    }

    /**
     * {@inheritdoc}
     */
    public function executeQuery($query)
    {
        if ($query instanceof QueryBuilder) {
            return $query->getQuery()->execute();
        }

        return $query->execute();
    }

    /**
     * {@inheritdoc}
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function getModelIdentifier($class)
    {
        return 'id';
    }

    /**
     * {@inheritdoc}
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function getIdentifierValues($entity)
    {
        return array();
    }

    /**
     * {@inheritdoc}
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function getIdentifierFieldNames($class)
    {
        return $this->getMetadata($class)->getIdentifierFieldNames();
    }

    /**
     * {@inheritdoc}
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function getNormalizedIdentifier($entity)
    {
        return $entity['id'];
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
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function addIdentifiersToQuery($class, ProxyQueryInterface $queryProxy, array $idx)
    {
    }

    /**
     * {@inheritdoc}
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function batchDelete($class, ProxyQueryInterface $queryProxy)
    {
    }

    /**
     * {@inheritdoc}
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function getDataSourceIterator(DatagridInterface $datagrid, array $fields, $firstResult = null, $maxResult = null)
    {
    }

    /**
     * {@inheritdoc}
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function getExportFields($class)
    {
        $metadata = $this->entityManager->getClassMetadata($class);

        return $metadata->getFieldNames();
    }

    /**
     * {@inheritdoc}
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function getModelInstance($class)
    {
        return new ArrayObject(array(), ArrayObject::ARRAY_AS_PROPS);
    }

    /**
     * {@inheritdoc}
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function getSortParameters(FieldDescriptionInterface $fieldDescription, DatagridInterface $datagrid)
    {
    }

    /**
     * {@inheritdoc}
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function getPaginationParameters(DatagridInterface $datagrid, $page)
    {
        $values = $datagrid->getValues();

        $values['_sort_by'] = $values['_sort_by']->getName();
        $values['_page'] = $page;

        return array('filter' => $values);
    }

    /**
     * {@inheritdoc}
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function getDefaultSortValues($class)
    {
        return array(
            '_sort_order' => 'ASC',
            '_sort_by' => implode(',', array($this->getModelIdentifier($class))),
            '_page' => 1,
            '_per_page' => 25,
        );
    }

    /**
     * {@inheritdoc}
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function getModelCollectionInstance($class)
    {
        return new \Doctrine\Common\Collections\ArrayCollection();
    }

    /**
     * {@inheritdoc}
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function collectionClear(&$collection)
    {
        return $collection->clear();
    }

    /**
     * {@inheritdoc}
     */
    public function collectionHasElement(&$collection, &$element)
    {
        return $collection->contains($element);
    }

    /**
     * {@inheritdoc}
     */
    public function collectionAddElement(&$collection, &$element)
    {
        return $collection->add($element);
    }

    /**
     * {@inheritdoc}
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
