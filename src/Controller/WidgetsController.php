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

namespace Splash\Admin\Controller;

use Sonata\AdminBundle\Controller\CRUDController;
use Splash\Admin\Model\ObjectManagerAwareTrait;
use Splash\Admin\Services\WidgetFactoryService;
use Splash\Core\SplashCore as Splash;
use Symfony\Component\HttpFoundation\Response;

/**
 * Admin Controller for Splash Widgets Explorer
 */
class WidgetsController extends CRUDController
{
    use ObjectManagerAwareTrait;

    /**
     * List action.
     *
     * @return Response
     */
    public function listAction()
    {
        //====================================================================//
        // Setup Connector
        $connector = $this->getConnector();
        //====================================================================//
        // Generate Splash Widgets
        $widgets = array();
        foreach ($connector->getAvailableWidgets() as $widgetType) {
            $widgets[$widgetType] = array(
                'service' => WidgetFactoryService::SERVICE,
                'type' => $widgetType.'@'.$connector->getWebserviceId(),
            );
        }
        //====================================================================//
        // Render Connector Profile Page
        return $this->render('@SplashAdmin/Widgets/list.html.twig', array(
            'action' => 'list',
            'admin' => $this->admin,
            'profile' => $connector->getProfile(),
            'Widgets' => $widgets,
            'log' => Splash::log()->GetHtmlLog(true),
        ));
    }
}
