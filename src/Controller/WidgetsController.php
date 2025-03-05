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

namespace Splash\Admin\Controller;

use BadPixxel\Widgets\Dictionary\Widgets\RenderingModes;
use BadPixxel\Widgets\Helpers\RenderingConfiguration;
use BadPixxel\Widgets\Services\Widgets\WidgetsResolver;
use Exception;
use Sonata\AdminBundle\Controller\CRUDController;
use Splash\Admin\Model\ObjectManagerAwareTrait;
use Splash\Core\SplashCore as Splash;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Admin Controller for Splash Widgets Explorer
 */
class WidgetsController extends CRUDController
{
    use ObjectManagerAwareTrait;

    public function __construct(
        private readonly WidgetsResolver $widgetsResolver,
    ) {
    }

    /**
     * {@inheritDoc}
     *
     * @throws Exception
     */
    public function listAction(Request $request): Response
    {
        $serverId = $this->getObjectsManager()->getServerId();
        //====================================================================//
        // Rendering Config for Sonata Admin
        $configuration = new RenderingConfiguration(
            mode: RenderingModes::BS3
        );
        //====================================================================//
        // Get All Widget Configurators for this Server
        $configurators = $this->widgetsResolver->findAll($serverId);

        //====================================================================//
        // Render Connector Profile Page
        return $this->render('@SplashAdmin/Widgets/list.html.twig', array(
            'action' => 'list',
            'admin' => $this->admin,
            'profile' => $this->getConnector()->getProfile(),
            'configurators' => $configurators,
            'configuration' => $configuration,
            'log' => Splash::log()->getHtmlLog(true),
        ));
    }
}
