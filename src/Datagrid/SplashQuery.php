<?php
declare(strict_types=1);

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

namespace Splash\Admin\Datagrid;

use ArrayObject;
use InvalidArgumentException;
use Sonata\AdminBundle\Datagrid\ProxyQueryInterface;
use Splash\Admin\Model\ObjectsManager;
use Splash\Bundle\Interfaces\Connectors\PrimaryKeysInterface;
use Splash\Bundle\Services\ConnectorsManager;
use Splash\Components\FieldsManager;

/**
 * This class try to unify the query usage with Splash Webservice.
 */
class SplashQuery implements ProxyQueryInterface
{
    /**
     * @var ConnectorsManager
     */
    protected ConnectorsManager $connectorsManager;

    /**
     * @var ObjectsManager
     */
    protected ObjectsManager $objectsManager;

    /**
     * @var null|string
     */
    protected ?string $sortBy;

    /**
     * @var null|string
     */
    protected ?string $sortOrder;

    /**
     * @var null|string
     */
    protected ?string $filterBy = null;

    /**
     * @var array<string, string>
     */
    protected array $primary = array();

    /**
     * @var int
     */
    protected int $uniqueParameterId = 0;

    /**
     * The index of the first result to retrieve.
     *
     * @var int
     */
    private int $firstResult = 0;

    /**
     * The maximum number of results to retrieve.
     *
     * @var int
     */
    private int $maxResults = 25;

    /**
     * @param ConnectorsManager $connectorsManager
     * @param ObjectsManager    $objectsManager
     */
    public function __construct(ConnectorsManager $connectorsManager, ObjectsManager $objectsManager)
    {
        $this->connectorsManager = $connectorsManager;
        $this->objectsManager = $objectsManager;
    }

    /**
     * {@inheritdoc}
     */
    public function __call($name, $args)
    {
        /** @phpstan-ignore-next-line  */
        return \call_user_func_array(array($this, $name), $args);
    }

    /**
     * {@inheritdoc}
     */
    public function execute(): SplashCollection
    {
        //====================================================================//
        // Load Current Connector
        $connector = $this->objectsManager->getConnector();
        $objectsType = $this->objectsManager->getObjectType();
        $objectsFields = $this->objectsManager->getObjectFields();
        //====================================================================//
        // Check if Connector is Primary Aware
        if ($this->hasPrimary() && ($connector instanceof PrimaryKeysInterface)) {
            //====================================================================//
            // Load Object ID by Primary from Splash Connector
            $primaryObjectId = $connector->getObjectIdByPrimary(
                $objectsType,
                $this->getPrimary()
            );
            //====================================================================//
            // Load Object by ID from Splash Connector
            $primaryObject = $primaryObjectId ? $connector->getObject(
                $objectsType,
                $primaryObjectId,
                FieldsManager::reduceFieldList($objectsFields, true)
            ) : null;
            //====================================================================//
            // Detection by Primary Worked
            if ($primaryObject) {
                $objectsList = array(
                    $primaryObjectId => $primaryObject,
                    'meta' => array('total' => 1, 'current' => 1)
                );
            } else {
                $objectsList = array(
                    'meta' => array('total' => 0, 'current' => 0)
                );
            }
        } else {
            //====================================================================//
            // Load Objects List from Splash Connector
            $objectsList = $connector->getObjectList(
                $this->objectsManager->getObjectType(),
                $this->getFilterBy(),
                $this->getQueryParameters()
            );
        }
        //====================================================================//
        // Catch Splash Logs
        $this->connectorsManager->pushLogToSession(true);
        //====================================================================//
        // Parse Object List
        $results = new SplashCollection();
        foreach ($objectsList as $key => $item) {
            if ("meta" == $key) {
                $results->setTotalResults((int) $item['total']);

                continue;
            }
            $results->add(new ArrayObject($item, ArrayObject::ARRAY_AS_PROPS));
        }

        return $results;
    }

    /**
     * Build Splash Query Parameters Array
     */
    public function getQueryParameters(): array
    {
        return array(
            "max" => $this->getMaxResults(),
            "offset" => $this->getFirstResult(),
        );
    }

    /**
     * @param array $parentAssociationMappings
     * @param array $fieldMapping
     *
     * @return $this
     */
    public function setSortBy($parentAssociationMappings, $fieldMapping): self
    {
        $alias = $this->entityJoin($parentAssociationMappings);
        $this->sortBy = $alias.'.'.$fieldMapping['fieldName'];

        return $this;
    }

    /**
     * @return null|string
     */
    public function getSortBy(): ?string
    {
        return $this->sortBy;
    }

    /**
     * @param $sortOrder
     *
     * @return $this
     */
    public function setSortOrder($sortOrder): self
    {
        if (!in_array(strtoupper($sortOrder), $validSortOrders = array('ASC', 'DESC'), true)) {
            throw new InvalidArgumentException(sprintf(
                '"%s" is not a valid sort order, valid values are "%s"',
                $sortOrder,
                implode(', ', $validSortOrders)
            ));
        }
        $this->sortOrder = $sortOrder;

        return $this;
    }

    /**
     * @return null|string
     */
    public function getSortOrder(): ?string
    {
        return $this->sortOrder;
    }

    /**
     * NEXT_MAJOR: Remove this method.
     *
     * @deprecated since sonata-project/doctrine-orm-admin-bundle 3.31, to be removed in 4.0.
     */
    public function getSingleScalarResult()
    {
        return $this->execute()->count();
    }

    /**
     * @param int $firstResult
     *
     * @return $this
     */
    public function setFirstResult($firstResult): self
    {
        $this->firstResult = $firstResult;

        return $this;
    }

    /**
     * @return int
     */
    public function getFirstResult(): int
    {
        return $this->firstResult;
    }

    /**
     * @param int $maxResults
     *
     * @return $this
     */
    public function setMaxResults($maxResults): self
    {
        $this->maxResults = $maxResults;

        return $this;
    }

    /**
     * @return int
     */
    public function getMaxResults(): int
    {
        return $this->maxResults;
    }

    /**
     * @param null|string $filterBy
     *
     * @return $this
     */
    public function setFilterBy(?string $filterBy): self
    {
        $this->filterBy = $filterBy;

        return $this;
    }

    /**
     * @return null|string
     */
    public function getFilterBy(): ?string
    {
        return $this->filterBy;
    }

    /**
     * Add a Primary Key to Query
     *
     * @param string $key
     * @param string $value
     *
     * @return $this
     */
    public function addPrimary(string $key, string $value): self
    {
        $this->primary[$key] = $value;

        return $this;
    }

    /**
     * Get Primary Keys Array
     *
     * @return array<string, string>
     */
    public function getPrimary(): array
    {
        return $this->primary;
    }

    /**
     * Has primary keys Filters
     *
     * @return bool
     */
    public function hasPrimary(): bool
    {
        return !empty($this->primary);
    }

    /**
     * @return int
     */
    public function getUniqueParameterId(): int
    {
        return $this->uniqueParameterId++;
    }

    /**
     * @param array $associationMappings
     *
     * @return string
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function entityJoin(array $associationMappings): string
    {
        return "o";
    }
}
