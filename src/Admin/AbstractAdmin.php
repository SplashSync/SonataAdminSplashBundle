<?php

/*
 *  This file is part of SplashSync Project.
 *
 *  Copyright (C) 2015-2021 Splash Sync  <www.splashsync.com>
 *
 *  This program is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 *
 *  For the full copyright and license information, please view the LICENSE
 *  file that was distributed with this source code.
 */

namespace Splash\Admin\Admin;

use Sonata\AdminBundle\Admin\AbstractAdmin as BaseAdmin;
use Sonata\AdminBundle\FieldDescription\FieldDescriptionFactoryInterface;
use Splash\Admin\Model\ObjectsManager;

/**
 * Base Admin Class for Splash Sonata Admin Bundle
 */
abstract class AbstractAdmin extends BaseAdmin
{
    /**
     * Current Server Id
     *
     * @var string
     */
    private $serverId;

    /**
     * Current Object Type
     *
     * @var string
     */
    private $objectType;

    /**
     * @param string       $code
     * @param class-string $class
     * @param string       $baseControllerName
     * @param string       $serverId
     * @param string       $type
     */
    public function __construct($code, $class, $baseControllerName, $serverId, $type)
    {
        parent::__construct($code, $class, $baseControllerName);
        $this->baseRouteName = 'sonata_admin_'.$code.'_'.$type;
        $this->baseRoutePattern = $serverId.'/'.$type;
        $this->setServerId($serverId);
    }

    //====================================================================//
    // Admin Model Manager Managements
    //====================================================================//

    /**
     * Configure Admin Service
     */
    public function configure(): void
    {
        //====================================================================//
        // Setup Model Manager
        $this->configureModelManager();
    }

    /**
     *  Disable Usage of Field Description Factory
     */
    public function getFieldDescriptionFactory(): ?FieldDescriptionFactoryInterface
    {
        return null;
    }

    //====================================================================//
    // Objects Managements
    //====================================================================//

    /**
     * Get Current Object Type
     *
     * @return string
     */
    public function getObjectType()
    {
        //====================================================================//
        // Load From cache
        if (!empty($this->objectType)) {
            return $this->objectType;
        }
        //====================================================================//
        // Detect Forced Object Type from Request
        if ($this->getRequest()->get('ObjectType')) {
            $this->objectType = $this->getRequest()->get('ObjectType');
        }
        //====================================================================//
        // Detect Object Type from Session
        if (empty($this->objectType)) {
            $this->objectType = $this->getRequest()->getSession()->get('ObjectType');
        }
        //====================================================================//
        // Load Object Types from Connector
        /** @var ObjectsManager $modelManager */
        $modelManager = $this->getModelManager();
        $objectTypes = $modelManager->getConnector()->getAvailableObjects();
        //====================================================================//
        // No Object Type? Take First Available from Connector
        if (empty($this->objectType) || !in_array($this->objectType, $objectTypes, true)) {
            $this->objectType = array_shift($objectTypes);
        }

        return $this->objectType;
    }

    /**
     * Get Current Server Id
     *
     * @return string
     */
    public function getServerId()
    {
        return $this->serverId;
    }

    /**
     * Configure Splash Objects Manager
     */
    protected function configureModelManager(): void
    {
        //====================================================================//
        // Load Container
        $container = $this->getConfigurationPool()->getContainer();
        if (empty($container)) {
            return;
        }
        //====================================================================//
        // Load Model Manager
        /** @var ObjectsManager $modelManager */
        $modelManager = $container->get('sonata.admin.manager.splash');
        //====================================================================//
        // Setup Model Manager
        $modelManager->setServerId($this->serverId);
        //====================================================================//
        // Override Model Manager
        $this->setModelManager($modelManager);
    }

    //====================================================================//
    // Basic Getters & Setters
    //====================================================================//

    /**
     * Setup Splash Server Id
     *
     * @param string $serverId
     *
     * @return self
     */
    protected function setServerId(string $serverId)
    {
        $this->serverId = $serverId;

        return $this;
    }
}
