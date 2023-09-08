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

use Exception;
use Splash\Bundle\Models\AbstractConnector;

/**
 * @abstract    Admin Controller Access to Object Manager
 */
trait ObjectManagerAwareTrait
{
    /**
     * @abstract    Get Splash Object Manager
     *
     * @throws Exception
     *
     * @return ObjectsManager
     */
    public function getObjectsManager(): ObjectsManager
    {
        //====================================================================//
        // Get Object Manager
        $objectManager = $this->admin->getModelManager();
        if (!($objectManager instanceof ObjectsManager)) {
            throw new Exception('Splash Object Manager Not Found');
        }

        return $objectManager;
    }

    /**
     * Get Currently Used Connector
     *
     * @throws Exception
     *
     * @return AbstractConnector
     */
    public function getConnector(): AbstractConnector
    {
        return $this->getObjectsManager()->getConnector();
    }
}
