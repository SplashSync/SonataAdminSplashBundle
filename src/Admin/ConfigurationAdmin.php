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

namespace Splash\Admin\Admin;

use Sonata\AdminBundle\Route\RouteCollectionInterface;

/**
 * Connectors Configuration Admin Class for Splash Connectors Explorer
 */
class ConfigurationAdmin extends AbstractAdmin
{
    /**
     * Configure Connector Configuration Routes
     *
     * @param RouteCollectionInterface $collection
     */
    protected function configureRoutes(RouteCollectionInterface $collection): void
    {
        $collection->remove('batch');
        $collection->remove('create');
        $collection->remove('edit');
        $collection->remove('show');
        $collection->remove('delete');
        $collection->remove('export');
    }
}
