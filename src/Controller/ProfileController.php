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

use ArrayObject;
use Sonata\AdminBundle\Controller\CRUDController;
use Splash\Admin\Admin\ProfileAdmin;
use Splash\Admin\Model\ObjectManagerAwareTrait;
use Splash\Core\SplashCore as Splash;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Description of ObjectCRUDController
 *
 * @author nanard33
 */
class ProfileController extends CRUDController
{
    use ObjectManagerAwareTrait;

    /**
     * {@inheritDoc}
     */
    public function listAction(Request $request):Response
    {
        $results = array();
        //====================================================================//
        // Setup Connector
        $connector = $this->getConnector();
        //====================================================================//
        // Execute Splash Self-Test
        $results['selftest'] = $connector->selfTest();
        if ($results['selftest']) {
            Splash::log()->msg("Self-Test Passed");
        }
        $logSelfTest = Splash::log()->GetHtmlLog(true);
        //====================================================================//
        // Execute Splash Ping Test
        $results['ping'] = $results['selftest'] ? $connector->ping() : false;
        $logPingTest = Splash::log()->GetHtmlLog(true);
        //====================================================================//
        // Execute Splash Connect Test
        $results['connect'] = $results['selftest'] ? $connector->connect() : false;
        $logConnectTest = Splash::log()->GetHtmlLog(true);
        //====================================================================//
        // Load Connector Informations
        $informations = array();
        if ($results['ping'] && $results['connect']) {
            $informations = $connector->informations(new ArrayObject(array()));
        }
        //====================================================================//
        // Load Objects Informations
        $objects = array();
        foreach ($connector->getAvailableObjects() as $objectType) {
            $objects[$objectType] = $connector->getObjectDescription($objectType);
        }

        //====================================================================//
        // Render Connector Profile Page
        return $this->render("@SplashAdmin/Profile/list.html.twig", array(
            'action' => 'list',
            'admin' => $this->admin,
            "profile" => $connector->getProfile(),
            "infos" => $informations,
            "config" => Splash::configuration(),
            "results" => $results,
            "selftest" => $logSelfTest,
            "ping" => $logPingTest,
            "connect" => $logConnectTest,
            "objects" => $objects,
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
            "log" => Splash::log()->GetHtmlLog(true),
        ));
    }
}
