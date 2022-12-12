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

use Knp\Menu\ItemInterface;
use Sonata\AdminBundle\Admin\AdminInterface;
use Sonata\AdminBundle\Route\RouteCollection;

/**
 * Profile View Admin Class for Splash Connectors Explorer
 */
class ProfileAdmin extends AbstractAdmin
{
    /**
     * @var null|string
     */
    public static ?string $objectType = null;

    /**
     * @param RouteCollection $collection
     */
    protected function configureRoutes(RouteCollection $collection): void
    {
        $collection->remove('batch');
        $collection->remove('create');
        $collection->remove('edit');
        $collection->remove('delete');
        $collection->remove('export');
    }

    /**
     * Configure Child Admins (Notary!!).
     *
     * @param ItemInterface  $menu
     * @param string         $action
     * @param AdminInterface $childAdmin
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    protected function configureSideMenu(ItemInterface $menu, $action, AdminInterface $childAdmin = null): void
    {
        //==============================================================================
        // SHOW Fields Mode
        if (self::$objectType) {
            //==============================================================================
            // All Fields
            $menu->addChild('All', array(
                'uri' => $this->generateUrl('show', array('id' => self::$objectType)),
            ))
                ->setAttribute('icon', 'fa fa-times text-default')
                ->setAttribute('title', 'Show All Fields')
            ;
            //==============================================================================
            // Required Fields
            $menu->addChild('Required', array(
                'uri' => $this->generateUrl('show', array('id' => self::$objectType, 'filter' => 'required')),
            ))
                ->setAttribute('icon', 'fa fa-exclamation-triangle text-danger')
                ->setAttribute('title', 'Show only required Fields')
            ;
            //==============================================================================
            // Listed Fields
            $menu->addChild('Listed', array(
                'uri' => $this->generateUrl('show', array('id' => self::$objectType, 'filter' => 'inlist')),
            ))
                ->setAttribute('icon', 'fa fa-list text-success')
                ->setAttribute('title', 'Show only listed Fields')
            ;
            //==============================================================================
            // Primary Fields
            $menu->addChild('Primary', array(
                'uri' => $this->generateUrl('show', array('id' => self::$objectType, 'filter' => 'primary')),
            ))
                ->setAttribute('icon', 'fa fa-database text-danger')
                ->setAttribute('title', 'Show only primary Fields')
            ;
            //==============================================================================
            // Write Fields
            $menu->addChild('Write', array(
                'uri' => $this->generateUrl('show', array('id' => self::$objectType, 'filter' => 'write')),
            ))
                ->setAttribute('icon', 'fa fa-pencil text-primary')
                ->setAttribute('title', 'Show only Write Fields')
            ;
            //==============================================================================
            // Indexed Fields
            $menu->addChild('Indexed', array(
                'uri' => $this->generateUrl('show', array('id' => self::$objectType, 'filter' => 'index')),
            ))
                ->setAttribute('icon', 'fa fa-search text-primary')
                ->setAttribute('title', 'Show only Indexed Fields')
            ;
            //==============================================================================
            // Indexed Fields
            $menu->addChild('No Tests', array(
                'uri' => $this->generateUrl('show', array('id' => self::$objectType, 'filter' => 'notest')),
            ))
                ->setAttribute('icon', 'fa fa-bug text-warning')
                ->setAttribute('title', 'Show only NON Tested Fields')
            ;
        }
    }
}
