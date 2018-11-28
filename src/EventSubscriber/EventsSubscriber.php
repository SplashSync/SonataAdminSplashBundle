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

namespace Splash\Admin\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;

use Doctrine\ORM\EntityManager;

use Splash\Bundle\Events\ObjectsCommitEvent;
use Splash\Bundle\Events\UpdateConfigurationEvent;
use Splash\Bundle\Services\ConnectorsManager;

use Splash\Admin\Entity\SplashServer;

/**
 * Symfony Events Subscriber
 */
class EventsSubscriber implements EventSubscriberInterface
{
    
    /**
     * @abstract    Splash Connectors Manager
     * @var ConnectorsManager
     */
    private $Manager;
    
    /**
     * @abstract    Doctrine Entity Manager
     * @var EntityManager
     */
    private $EntityManager;
    
    //====================================================================//
    //  CONSTRUCTOR
    //====================================================================//
    
    /**
     * @abstract    Service Constructor
     */
    public function __construct(ConnectorsManager $Manager, EntityManager $EntityManager)
    {
        //====================================================================//
        // Store Splash Connectors Manager
        $this->Manager          =   $Manager;
        //====================================================================//
        // Store Entity Manager
        $this->EntityManager    =   $EntityManager;
    }
    
    //====================================================================//
    //  SUBSCRIBER
    //====================================================================//
    
    /**
     * @abstract    Configure Event Subscriber
     * @return  void
     */
    public static function getSubscribedEvents()
    {
        return array(
            // Connector Objects Commit Events
            ObjectsCommitEvent::NAME   => array(
               array('onObjectCommit', 100)
            ),
            // Connectors Update Configuration Events
            UpdateConfigurationEvent::NAME   => array(
               array('onSave', 100)
            ),
        );
    }

    //====================================================================//
    //  EVENTS ACTIONS
    //====================================================================//

    /**
     * @abstract    On Standalone Object Commit Event
     * @param   ObjectsCommitEvent $event
     * @return  void
     */
    public function onObjectCommit(ObjectsCommitEvent $event)
    {
        //====================================================================//
        // Detect Pointed Server Host
        $ServerId   = $this->Manager->hasWebserviceConfiguration($event->getServerId());
        $Host       = $this->Manager->getWebserviceHost($ServerId);
        //====================================================================//
        // If Server Host is False or Empty 
        // => Stop Event Propagation to Avoid Tying to Commit
        if (!$Host) {
            $event->stopPropagation();
        }
    }
    
    /**
     * @abstract    On Connector Configuration Update Event
     * @param   UpdateConfigurationEvent $event
     * @return  void
     */
    public function onSave(UpdateConfigurationEvent $event)
    {     
        $ServerId   =   $this->Manager->hasWebserviceConfiguration($event->getWebserviceId());
        //====================================================================//
        // Detect Pointed Server Host
        if ($ServerId) {
            //====================================================================//
            // Load Configuration from DataBase if Exists
            $DbConfig   = $this->EntityManager->getRepository("AppExplorerBundle:SplashServer")->findOneByIdentifier($ServerId);
            //====================================================================//
            // Not Found => Create Configuration
            if (empty($DbConfig)) {
                $DbConfig   =   new SplashServer();
                $DbConfig->setIdentifier($ServerId);
            }
            //====================================================================//
            // Update Configuration
            $DbConfig->setSettings($event->getConfiguration());        
            $this->EntityManager->persist($DbConfig);
            $this->EntityManager->flush();
            $this->EntityManager->clear();         
        }
        //====================================================================//
        // Stop Event Propagation to Avoid Tying to Commit
        $event->stopPropagation();
    }
    
}
