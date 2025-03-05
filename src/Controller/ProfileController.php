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

use Sonata\AdminBundle\Controller\CRUDController;
use Splash\Admin\Admin\ProfileAdmin;
use Splash\Admin\Model\ObjectManagerAwareTrait;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Description of ObjectCRUDController
 */
class ProfileController extends CRUDController
{
    use ObjectManagerAwareTrait;

    /**
     * {@inheritDoc}
     */
    public function listAction(Request $request):Response
    {
        //====================================================================//
        // Setup Connector
        $connector = $this->getConnector();

        //====================================================================//
        // Render Connector Profile Page
        return $this->render("@SplashAdmin/Profile/list.html.twig", array(
            'action' => 'list',
            'admin' => $this->admin,
            "connector" => $connector,
            "profile" => $connector->getProfile(),
        ));
    }

    /**
     * {@inheritDoc}
     */
    public function showAction(Request $request): Response
    {
        //====================================================================//
        // Enable Fields List SideMenu
        /** @var scalar $objectType */
        $objectType = $request->get("id");
        ProfileAdmin::$objectType = $objectType = (string) $objectType;
        //====================================================================//
        // Setup Connector
        $connector = $this->getConnector();
        //====================================================================//
        // Filter Fields List if Requested
        /** @var null|string $filter */
        $filter = $request->get("filter");
        /** @var array[] $allFields */
        $allFields = $connector->getObjectFields($objectType);
        $fields = $filter ? array_filter($allFields, function ($field) use ($filter) {
            switch ($filter) {
                case "required":
                case "inlist":
                case "primary":
                case "write":
                case "notest":
                    return $field[$filter] ?? false;
                case "index":
                    return ($field[$filter] ?? false) || ($field["primary"] ?? false);
                default:
                    return true;
            }
        }) : $allFields;

        //====================================================================//
        // Render Connector Profile Page
        return $this->renderWithExtraParams("@SplashAdmin/Profile/show.html.twig", array(
            'action' => 'list',
            "profile" => $connector->getProfile(),
            "object" => $connector->getObjectDescription($objectType),
            "fields" => $fields,
            "filter" => $filter,
        ));
    }
}
