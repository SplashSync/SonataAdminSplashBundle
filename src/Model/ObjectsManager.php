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

use Doctrine\Common\Util\ClassUtils;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Query;
use Doctrine\ORM\QueryBuilder;
use RuntimeException;
use Sonata\AdminBundle\Datagrid\ProxyQueryInterface;
use Sonata\AdminBundle\Exception\ModelManagerException;
use Sonata\AdminBundle\Model\LockInterface;
use Sonata\AdminBundle\Model\ModelManagerInterface;
use Splash\Admin\Datagrid\SplashQuery;
use Splash\Bundle\Events\ObjectsIdChangedEvent;
use Splash\Bundle\Models\AbstractConnector;
use Splash\Bundle\Services\ConnectorsManager;
use Splash\Components\FieldsManager;
use Splash\Core\SplashCore as Splash;
use stdClass;
use Throwable;

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
     * @var EntityManager[]
     */
    protected array $cache = array();

    /**
     * @var string
     */
    private string $serverId;

    /**
     * @var bool
     */
    private bool $isShowMode = false;

    /**
     * @var null|string
     */
    private ?string $objectType = null;

    /**
     * Fields Cache
     *
     * @var array
     */
    private array $fields = array();

    /**
     * Definitions Cache
     *
     * @var null|array[]
     */
    private ?array $definitions;

    /**
     * Store New Object ID when Changed during Edit
     *
     * @var null|string
     */
    private ?string $newObjectId;

    /**
     * Class Constructor
     */
    public function __construct(
        private ConnectorsManager $manager,
        private EntityManager $entityManager
    ) {
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
    public function setObjectType(string $objectType): static
    {
        $this->objectType = $objectType;

        return $this;
    }

    /**
     * Get Current Splash Object Type
     *
     * @return null|string
     */
    public function getObjectType(): ?string
    {
        return $this->objectType;
    }

    /**
     * Select Splash Bundle Connection to Use
     *
     * @param string $serverId
     *
     * @return $this
     */
    public function setServerId(string $serverId): static
    {
        $this->serverId = $serverId;

        return $this;
    }

    /**
     * Select Show Mode to Allow reading of ReadOnly Fields
     *
     * @return $this
     */
    public function setShowMode(): static
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
    public function getConnector(): AbstractConnector
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
    public function getConfiguration(): array
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
    public function getNewObjectId(): ?string
    {
        return $this->newObjectId ?? null;
    }

    /**
     * Fetch Connector Available Objects List
     *
     * @return array
     */
    public function getObjectsDefinition(): array
    {
        if (!isset($this->definitions)) {
            //====================================================================//
            // Read Objects Type List
            $objectTypes = $this->getConnector()->getAvailableObjects();
            //====================================================================//
            // Read Description of All Objects
            $this->definitions = array();
            foreach ($objectTypes as $objectType) {
                $this->definitions[$objectType] = $this->getConnector()
                    ->getObjectDescription($objectType)
                ;
            }
        }

        return $this->definitions;
    }

    /**
     * Get Object Fields Array
     *
     * @return array[]
     */
    public function getObjectFields(): array
    {
        //====================================================================//
        // Safety Check
        if (empty($this->objectType)) {
            return array();
        }
        if (!isset($this->fields[$this->objectType])) {
            $this->fields[$this->objectType] = $this->getConnector()->getObjectFields($this->objectType);
        }

        return $this->fields[$this->objectType];
    }

    /**
     * @param class-string $class
     *
     * @return ClassMetadata
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function getMetadata(string $class): ClassMetadata
    {
        return new ClassMetadata('ArrayObject');
    }

    /**
     * @param class-string $class
     *
     * @return boolean
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function hasMetadata(string $class): bool
    {
        return false;
    }

    /**
     * Create A New Object via Connector
     *
     * @throws ModelManagerException
     */
    public function create(object $object): void
    {
        //====================================================================//
        // Safety Check
        if (!$object instanceof stdClass) {
            throw new ModelManagerException(
                sprintf("Splash Object must be an %s", stdClass::class)
            );
        }
        $object->id = null;
        //====================================================================//
        // Execute Reverse Transform
        $objectData = (array) $object;
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
                ->setObject((string) $this->objectType, null, $objectData)
            ;
        } catch (Throwable $e) {
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
    }

    /**
     * Update Object via Connector
     *
     * @throws ModelManagerException
     */
    public function update(object $object): void
    {
        //====================================================================//
        // Safety Check - Verify Object has Id
        if (empty($object->id) || !is_scalar($object->id)) {
            throw new ModelManagerException("Splash Object: Invalid ID");
        }
        //====================================================================//
        // Safety Check
        if (!$object instanceof stdClass) {
            throw new ModelManagerException(
                sprintf("Splash Object must be an %s", stdClass::class)
            );
        }
        //====================================================================//
        // Execute Reverse Transform
        $objectId = (string) $object->id;

        //====================================================================//
        // Do Object Update
        try {
            //====================================================================//
            // Write Object Data
            $this->getConnector()
                ->setObject((string) $this->objectType, $objectId, (array) $object)
            ;
        } catch (Throwable $e) {
            throw new ModelManagerException(
                sprintf('Failed to update object: %s (%s)', ClassUtils::getClass($object), $e->getMessage()),
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
     * @throws ModelManagerException
     */
    public function delete(object $object): void
    {
        //====================================================================//
        // Safety Check
        if (!$object instanceof stdClass) {
            throw new ModelManagerException(
                sprintf("Splash Object must be an %s", stdClass::class)
            );
        }

        try {
            //====================================================================//
            // Delete Object Data
            $this->getConnector()->deleteObject((string) $this->objectType, $object->id);
        } catch (Throwable $e) {
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
            return null;
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
     * {@inheritdoc}
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function find($class, $id): ?stdClass
    {
        if (!$this->objectType || empty($id)) {
            return null;
        }
        //====================================================================//
        // Prepare Readable Fields List
        $fields = FieldsManager::reduceFieldList(
            $this->getObjectFields(),
            true
        );
        //====================================================================//
        // Read Object Data
        $object = $this->getConnector()->getObject($this->objectType, (string) $id, $fields);
        //====================================================================//
        // Catch Splash Logs
        $this->manager->pushLogToSession(true);
        if (empty($object)) {
            return null;
        }

        //====================================================================//
        // Return Object
        return $this->modelTransform('ArrayObject', $object);
    }

    /**
     * {@inheritdoc}
     */
    public function findBy(string $class, array $criteria = array()): array
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
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function modelTransform(string $class, array $instance): stdClass
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
            return new stdClass();
        }

        //====================================================================//
        // Return Object
        return (object) $filtered;
    }

    /**
     * @param array $field
     * @param array $instance
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
    public function reverseTransform(object $object, array $array = array()): void
    {
        unset($array['id']);

        $object = (object) $array;
    }

    //====================================================================//
    // Unused Original Model Manager Functions
    //====================================================================//

    /**
     * {@inheritdoc}
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function findOneBy($class, array $criteria = array()): ?stdClass
    {
        return new stdClass();
    }

    /**
     * @param object $query
     *
     * @return bool
     */
    public function supportsQuery(object $query): bool
    {
        return $query instanceof SplashQuery;
    }

    /**
     * {@inheritdoc}
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function createQuery(string $class): ProxyQueryInterface
    {
        return new SplashQuery($this->manager, $this, $this->entityManager->createQueryBuilder());
    }

    /**
     * @param Query|QueryBuilder $query
     *
     * @return stdClass[]
     */
    public function executeQuery(object $query): iterable
    {
        if ($query instanceof QueryBuilder) {
            $result = $query->getQuery()->execute();
        } else {
            $result = $query->execute();
        }

        /** @var iterable<stdClass> $result */
        return $result;
    }

    /**
     * {@inheritdoc}
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function getModelIdentifier(string $class): string
    {
        return 'id';
    }

    /**
     * {@inheritdoc}
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function getIdentifierValues(object $model): array
    {
        return array();
    }

    /**
     * {@inheritdoc}
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function getIdentifierFieldNames($class): array
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
    public function getUrlSafeIdentifier($model): string
    {
        return (string) $this->getNormalizedIdentifier($model);
    }

    /**
     * {@inheritdoc}
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function addIdentifiersToQuery($class, ProxyQueryInterface $query, array $idx): void
    {
    }

    /**
     * {@inheritdoc}
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function batchDelete($class, ProxyQueryInterface $query): void
    {
    }

    /**
     * {@inheritdoc}
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function getExportFields($class): array
    {
        $metadata = $this->entityManager->getClassMetadata($class);

        return $metadata->getFieldNames();
    }

    /**
     * @param class-string $class
     *
     * @return stdClass
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function getModelInstance(string $class): stdClass
    {
        return new stdClass();
    }

    /**
     * method taken from Symfony\Component\PropertyAccess\PropertyAccessor.
     *
     * @param string $property
     *
     * @return mixed
     */
    protected function camelize(string $property): mixed
    {
        return str_replace(' ', '', ucwords(str_replace('_', ' ', $property)));
    }
}
