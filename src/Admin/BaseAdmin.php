<?php
/**
 * This file is part of SplashSync Project.
 *
 * Copyright (C) Splash Sync <www.splashsync.com>
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 * 
 * @author Bernard Paquier <contact@splashsync.com>
 */

namespace Splash\Admin\Admin;

use ArrayObject;

use Sonata\AdminBundle\Admin\AbstractAdmin;
use Sonata\AdminBundle\Route\RouteCollection;
use Sonata\AdminBundle\Form\FormMapper;
use Sonata\AdminBundle\Show\ShowMapper;

use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;

//use Sonata\CoreBundle\Form\Type\CollectionType;
use Sonata\AdminBundle\Form\Type\CollectionType;
//use Symfony\Component\Form\Extension\Core\Type\CollectionType;

use Splash\Core\SplashCore as Splash;

use Splash\Admin\Fields\FormHelper;
use Splash\Admin\Form\FieldsListType;

use Splash\Admin\Model\ConnectorAwareAdminTrait;

/**
 * @abstract    Base Admin Class for Splash Sonata Admin Bundle
 */
abstract class BaseAdmin extends AbstractAdmin
{
    /**
     * @abstract    Current Server Id
     * @var string
     */
    private $serverId;
    
    /**
     * @abstract    Current Object Type
     * @var string
     */
    private $ObjectType;
    
    /**
     * @param string $code
     * @param string $class
     * @param string $baseControllerName
     * @param string $ServerId
     * @param string $Type
     */
    public function __construct($code, $class, $baseControllerName, $ServerId, $Type)
    {
        parent::__construct($code, $class, $baseControllerName);
        $this->baseRouteName    = "sonata_admin_" . $code . "_" . $Type;
        $this->baseRoutePattern = $ServerId . "/" . $Type;
        $this->setServerId($ServerId);
    }  
    
    //====================================================================//
    // Admin Model Manager Managements
    //====================================================================//

    public function configure()
    {
        //====================================================================//
        // Setup Model Manager     
        $this->configureModelManager();
    }
    
    protected function configureModelManager()
    {
        //====================================================================//
        // Load Model Manager
        $ModelManager   =   $this->getConfigurationPool()->getContainer()->get("sonata.admin.manager.splash");
        //====================================================================//
        // Setup Model Manager     
        $ModelManager->setServerId($this->serverId);      
        //====================================================================//
        // Override Model Manager
        $this->setModelManager($ModelManager);
    }    
    
    //====================================================================//
    // Objects Managements
    //====================================================================//
    
    public function getObjectType()
    {
        //====================================================================//
        // Load From cache
        if (!empty($this->ObjectType)) {
            return $this->ObjectType;
        }        
        //====================================================================//
        // Detect Object Type from Request
        $this->ObjectType =   $this->getRequest()->getSession()->get("ObjectType");
        //====================================================================//
        // Load Object Types from Connector
        $ObjectTypes  =   $this->getModelManager()->getConnector()->getAvailableObjects();
        //====================================================================//
        // No Object Type? Take First Available from Connector
        if (empty($this->ObjectType) || !in_array($this->ObjectType, $ObjectTypes)) {
            $this->ObjectType   =   array_shift($ObjectTypes);
        }
        return $this->ObjectType;
    }
    
    //====================================================================//
    // Basic Getters & Setters
    //====================================================================//
    
    /**
     * @abstract    Setup Splash Server Id
     * @param   string  $ConnexionName
     * @return  $this
     */
    protected function setServerId(string $ConnexionName)
    {
        $this->serverId    =   $ConnexionName;
        return $this;
    }
    
    public function getServerId()
    {
        return $this->serverId;
    }    
  
}
