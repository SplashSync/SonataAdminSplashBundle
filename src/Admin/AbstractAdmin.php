<?php

/*
 *  This file is part of SplashSync Project.
 *
 *  Copyright (C) 2015-2018 Splash Sync  <www.splashsync.com>
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
use Splash\Admin\Model\ObjectsManager;

/**
 * @abstract    Base Admin Class for Splash Sonata Admin Bundle
 */
abstract class AbstractAdmin extends BaseAdmin
{
    /**
     * @abstract    Current Server Id
     *
     * @var string
     */
    private $serverId;

    /**
     * @abstract    Current Object Type
     *
     * @var string
     */
    private $objectType;

    /**
     * @param string $code
     * @param string $class
     * @param string $baseControllerName
     * @param string $serverId
     * @param string $type
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
    public function configure()
    {
        //====================================================================//
        // Setup Model Manager
        $this->configureModelManager();
    }

    //====================================================================//
    // Objects Managements
    //====================================================================//

    /**
     * Get Current Object Type
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
        // Detect Object Type from Request
        $this->objectType = $this->getRequest()->getSession()->get('ObjectType');
        //====================================================================//
        // Load Object Types from Connector
        $objectTypes = $this->getModelManager()->getConnector()->getAvailableObjects();
        //====================================================================//
        // No Object Type? Take First Available from Connector
        if (empty($this->objectType) || !in_array($this->objectType, $objectTypes, true)) {
            $this->objectType = array_shift($objectTypes);
        }

        return $this->objectType;
    }

    /**
     * Get Current Server Id
     * @return string
     */
    public function getServerId()
    {
        return $this->serverId;
    }

    /**
     * Configure Splash Objects Manager
     * @return void
     */
    protected function configureModelManager()
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
     * @abstract    Setup Splash Server Id
     *
     * @param string $serverId
     *
     * @return $this
     */
    protected function setServerId(string $serverId)
    {
        $this->serverId = $serverId;

        return $this;
    }
}
