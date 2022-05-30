<?php

/*
 *  This file is part of SplashSync Project.
 *
 *  Copyright (C) Splash Sync  <www.splashsync.com>
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
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Util\ClassUtils;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Query;
use Doctrine\ORM\QueryBuilder;
use Exception;
use PDOException;
use RuntimeException;
use Sonata\AdminBundle\Datagrid\DatagridInterface;
use Sonata\AdminBundle\Datagrid\ProxyQueryInterface;
use Sonata\AdminBundle\Exception\ModelManagerException;
use Sonata\AdminBundle\FieldDescription\FieldDescriptionInterface;
use Sonata\AdminBundle\Model\LockInterface;
use Sonata\AdminBundle\Model\ModelManagerInterface;
use Sonata\DoctrineORMAdminBundle\Admin\FieldDescription;
use Sonata\DoctrineORMAdminBundle\Datagrid\ProxyQuery;
use Sonata\Exporter\Source\ArraySourceIterator;
use Sonata\Exporter\Source\SourceIteratorInterface;
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
     * @var string
     */
    private $objectType;

    /**
     * Fields Cache
     *
     * @var array
     */
    private array $fields = array();

    /**
     * Store New Object ID when Changed during Edit
     *
     * @var null|string
     */
    private ?string $newObjectId;

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
     * Get Current Splash Connector
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
            throw new RuntimeException('Unable to Identify linked Connector');
        }

        return $connector;
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
    public function getObjects(): array
    {
        //====================================================================//
        // Read Objects Type List
        return $this->getConnector()->getAvailableObjects();
    }

    /**
     * Detect Object ID was Changed so that we could redirect to New Id Page
     *
     * @param ObjectsIdChangedEvent $event
     */
    public function onObjectIdChangedEvent(ObjectsIdChangedEvent $event) : void
    {
        //====================================================================//
        // Store New Object Id
        $this->newObjectId = $event->getNewObjectId();
    }

    /**
     * If Object ID was Changed, Return New Object ID so that we could redirect to New ID Page
     *
     * @return null|string
     */
    public function getNewObjectId()
    {
        return $this->newObjectId ?? null;
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
     * @return array[]
     */
    public function getObjectFields()
    {
        if (!isset($this->fields[$this->objectType])) {
            $this->fields[$this->objectType] = $this->getConnector()->getObjectFields($this->objectType);
        }

        return $this->fields[$this->objectType];
    }

    /**
     * @param string $class
     *
     * @return ClassMetadata
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function getMetadata($class): ClassMetadata
    {
        return new ClassMetadata('ArrayObject');
    }

    /**
     * @param string $class
     *
     * @return boolean
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
            throw new RuntimeException('The name argument must be a string');
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
     *
     * @return ArrayObject
     */
    public function create($object): ArrayObject
    {
        $object->id = null;

        //====================================================================//
        // Execute Reverse Transform
        $objectData = $this
            ->modelReverseTransform('ArrayObject', $object->getArrayCopy())
            ->getArrayCopy();
        //====================================================================//
        // Remove Null Values
        foreach (array_keys($objectData) as $fieldId) {
            if (is_null($objectData[$fieldId])) {
                unset($objectData[$fieldId]);
            }
        }
        //====================================================================//
        // Write Object Data
        try {
            $object->id = $this->getConnector()
                ->setObject($this->objectType, null, $objectData);
        } catch (PDOException $e) {
            throw new ModelManagerException(
                sprintf('Failed to create object: %s', ClassUtils::getClass($object)),
                $e->getCode(),
                $e
            );
        } catch (Exception $e) {
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
        // Catch Create Fails
        if (empty($object->id)) {
            throw new ModelManagerException(
                sprintf('Failed to create object: %s', ClassUtils::getClass($object))
            );
        }

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
    public function update($object): void
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
                ->setObject($this->objectType, $objectId, $objectData->getArrayCopy());
        } catch (PDOException $e) {
            throw new ModelManagerException(
                sprintf('Failed to update object: %s', ClassUtils::getClass($object)),
                $e->getCode(),
                $e
            );
        } catch (Exception $e) {
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
    public function delete($object): void
    {
        try {
            //====================================================================//
            // Delete Object Data
            $this->getConnector()
                ->deleteObject($this->objectType, $object->id);
        } catch (PDOException $e) {
            throw new ModelManagerException(
                sprintf('Failed to delete object: %s', ClassUtils::getClass($object)),
                $e->getCode(),
                $e
            );
        } catch (Exception $e) {
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

        if (!$metadata->isVersioned || is_null($metadata->reflFields[$metadata->versionField])) {
            return;
        }

        return $metadata->reflFields[$metadata->versionField]->getValue($object);
    }

    /**
     * @param mixed $object
     * @param mixed $expectedVersion
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function lock($object, $expectedVersion): void
    {
    }

    /**
     * @param class-string    $class
     * @param null|int|string $objectId
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
        $object = $this->getConnector()->getObject($this->objectType, (string) $objectId, $fields);
        //====================================================================//
        // Catch Splash Logs
        $this->manager->pushLogToSession(true);
        if (empty($object)) {
            return new ArrayObject(array());
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
        //====================================================================//
        // Load Objects List from Splash Connector
        $list = $this->getConnector()->getObjectList($class, null, $criteria);
        //====================================================================//
        // Catch Splash Logs
        $this->manager->pushLogToSession(true);
        //====================================================================//
        // Return Objects List
        return $list;
    }

    /**
     * @param string       $class
     * @param array|object $instance
     *
     * @return ArrayObject
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function modelTransform($class, $instance)
    {
        //====================================================================//
        // Detect Empty Lists
        foreach ($this->getObjectFields() as $field) {
            $this->modelCompleteField($field, $instance);
        }
        //====================================================================//
        // Prepare Writable Fields List
        $writeFields = FieldsManager::reduceFieldList(
            $this->getObjectFields(),
            $this->isShowMode,
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
     * @param array $field
     * @param array $instance
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function modelCompleteField(array $field, &$instance): void
    {
        //====================================================================//
        // Only for Writable Fields
        if (!$this->isShowMode && empty($field["write"])) {
            return;
        }
        //====================================================================//
        // If List Fields
        if (FieldsManager::isListField($field["type"])) {
            // Get List Name
            $listName = FieldsManager::listName($field["id"]);
            if (empty($instance[$listName])) {
                $instance[$listName] = array();
            }

            return;
        }
        //====================================================================//
        // If Simple Fields
        if (!isset($instance[$field["id"]])) {
            $instance[$field["id"]] = null;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function modelReverseTransform($class, array $array = array()): ArrayObject
    {
        unset($array['id']);

        return new ArrayObject($array, ArrayObject::ARRAY_AS_PROPS);
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
     * @param array        $parentAssocMapping
     * @param class-string $class
     *
     * @return mixed
     */
    public function getParentFieldDescription($parentAssocMapping, $class)
    {
        $fieldName = $parentAssocMapping['fieldName'];
        $metadata = $this->getMetadata($class);
        $associatingMapping = $metadata->associationMappings[$fieldName];
        $fieldDescription = $this->getNewFieldDescriptionInstance($class, $fieldName);
        $fieldDescription->setName($fieldName);
        $fieldDescription->setAssociationMapping($associatingMapping);

        return $fieldDescription;
    }

    /**
     * @param class-string $class
     * @param string       $alias
     *
     * @return ProxyQuery
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function createQuery($class, $alias = 'o')
    {
        return new ProxyQuery(new QueryBuilder($this->entityManager));
    }

    /**
     * @param Query|QueryBuilder $query
     *
     * @return mixed
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
    public function getIdentifierValues($model)
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
     * @param array|object $model
     *
     * @return null|string
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function getNormalizedIdentifier($model): ?string
    {
        if (is_array($model)) {
            return (string) $model['id'];
        }

        if (isset($model->id)) {
            return (string) $model->id;
        }

        return null;
    }

    /**
     * The ORM implementation does nothing special but you still should use
     * this method when using the id in a URL to allow for future improvements.
     *
     * @param array|object $model
     *
     * @return string
     */
    public function getUrlsafeIdentifier($model): string
    {
        return (string) $this->getNormalizedIdentifier($model);
    }

    /**
     * {@inheritdoc}
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function addIdentifiersToQuery($class, ProxyQueryInterface $queryProxy, array $idx): void
    {
    }

    /**
     * {@inheritdoc}
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function batchDelete($class, ProxyQueryInterface $queryProxy): void
    {
    }

    /**
     * @param DatagridInterface $datagrid
     * @param array             $fields
     * @param null|int          $firstResult
     * @param null|int          $maxResult
     *
     * @return SourceIteratorInterface
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function getDataSourceIterator(
        DatagridInterface $datagrid,
        array $fields,
        $firstResult = null,
        $maxResult = null
    ) {
        return new ArraySourceIterator(array());
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
     * @param FieldDescriptionInterface $fieldDescription
     * @param DatagridInterface         $datagrid
     *
     * @return array
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function getSortParameters(FieldDescriptionInterface $fieldDescription, DatagridInterface $datagrid): array
    {
        return array();
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
     * @param class-string $class
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function getDefaultSortValues($class)
    {
        return array(
            '_sort_order' => 'ASC',
            '_sort_by' => implode(',', array($this->getModelIdentifier($class))),
            '_page' => (string) 1,
            '_per_page' => (string) 25,
        );
    }

    /**
     * {@inheritdoc}
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function getModelCollectionInstance($class)
    {
        return new ArrayCollection();
    }

    /**
     * @param array|ArrayCollection $collection
     *
     * @return void
     */
    public function collectionClear(&$collection)
    {
        if (is_array($collection)) {
            $collection = array();

            return;
        }

        $collection->clear();
    }

    /**
     * @param array|ArrayCollection $collection
     * @param object                $element
     *
     * @return bool
     */
    public function collectionHasElement(&$collection, &$element)
    {
        if (is_array($collection)) {
            return false;
        }

        return $collection->contains($element);
    }

    /**
     * @param array|ArrayCollection $collection
     * @param object                $element
     *
     * @return bool
     */
    public function collectionAddElement(&$collection, &$element)
    {
        if (is_array($collection)) {
            return false;
        }

        return $collection->add($element);
    }

    /**
     * @param array|ArrayCollection $collection
     * @param object                $element
     *
     * @return bool
     */
    public function collectionRemoveElement(&$collection, &$element)
    {
        if (is_array($collection)) {
            return false;
        }

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
