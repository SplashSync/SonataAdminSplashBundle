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

namespace Splash\Admin\EventSubscriber;

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
    private ConnectorsManager $manager;

    //====================================================================//
    //  CONSTRUCTOR
    //====================================================================//

    /**
     * Service Constructor
     *
     * @param ConnectorsManager $manager
     */
    public function __construct(ConnectorsManager $manager)
    {
        //====================================================================//
        // Store Splash Connectors Manager
        $this->manager = $manager;
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
