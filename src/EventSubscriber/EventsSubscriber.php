<?php

/*
 *  This file is part of SplashSync Project.
 *
 *  Copyright (C) 2015-2020 Splash Sync  <www.splashsync.com>
 *
 *  This program is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 *
 *  For the full copyright and license information, please view the LICENSE
 *  file that was distributed with this source code.
 */

namespace Splash\Admin\EventSubscriber;

use Doctrine\ORM\EntityManager;
use Splash\Bundle\Events\ObjectsCommitEvent;
use Splash\Bundle\Services\ConnectorsManager;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Symfony Events Subscriber.
 */
class EventsSubscriber implements EventSubscriberInterface
{
    /**
     * Splash Connectors Manager
     *
     * @var ConnectorsManager
     */
    private $manager;

    /**
     * Doctrine Entity Manager
     *
     * @var EntityManager
     */
    private $entityManager;

    //====================================================================//
    //  CONSTRUCTOR
    //====================================================================//

    /**
     * Service Constructor
     *
     * @param ConnectorsManager $manager
     * @param EntityManager     $entityManager
     */
    public function __construct(ConnectorsManager $manager, EntityManager $entityManager)
    {
        //====================================================================//
        // Store Splash Connectors Manager
        $this->manager = $manager;
        //====================================================================//
        // Store Entity Manager
        $this->entityManager = $entityManager;
    }

    //====================================================================//
    //  SUBSCRIBER
    //====================================================================//

    /**
     * Configure Event Subscriber
     *
     * @return array
     */
    public static function getSubscribedEvents()
    {
        return array(
            // Connector Objects Commit Events
            ObjectsCommitEvent::NAME => array(
                array('onObjectCommit', 100),
            ),
        );
    }

    //====================================================================//
    //  EVENTS ACTIONS
    //====================================================================//

    /**
     * On Standalone Object Commit Event
     *
     * @param ObjectsCommitEvent $event
     *
     * @return void
     */
    public function onObjectCommit(ObjectsCommitEvent $event): void
    {
        //====================================================================//
        // Detect Pointed Server Host
        $serverId = $this->manager->hasWebserviceConfiguration($event->getWebserviceId());
        $host = $this->manager->getWebserviceHost((string) $serverId);
        //====================================================================//
        // If Server Host is False or Empty
        // => Stop Event Propagation to Avoid Commit
        if (!$host) {
            $event->stopPropagation();
        }
    }
}
