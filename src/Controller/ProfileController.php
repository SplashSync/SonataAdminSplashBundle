<?php

/*
 *  This file is part of SplashSync Project.
 *
 *  Copyright (C) 2015-2018 Splash Sync  <www.splashsync.com>
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
use Splash\Admin\Model\ObjectManagerAwareTrait;
use Splash\Core\SplashCore as Splash;
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
     * List action.
     *
     * @return Response
     */
    public function listAction()
    {
        $results = array();
        //====================================================================//
        // Setup Connector
        $connector  =   $this->getConnector();
        //====================================================================//
        // Execute Splash Self-Test
        $results['selftest'] = $connector->selfTest();
        if ($results['selftest']) {
            Splash::log()->msg("Self-Test Passed");
        }
        $logSelfTest = Splash::log()->GetHtmlLog(true);
        //====================================================================//
        // Execute Splash Ping Test
        $results['ping']    = $results['selftest'] ? $connector->ping() : false;
        $logPingTest       = Splash::log()->GetHtmlLog(true);
        //====================================================================//
        // Execute Splash Connect Test
        $results['connect'] = $results['selftest'] ? $connector->connect() : false;
        $logConnectTest    = Splash::log()->GetHtmlLog(true);
        //====================================================================//
        // Load Connector Informations
        $informations    = array();
        if ($results['ping'] && $results['connect']) {
            $informations    = $connector->informations(new ArrayObject(array()));
        }
        //====================================================================//
        // Load Objects Informations
        $objects   =   array();
        foreach ($connector->getAvailableObjects() as $objectType) {
            $objects[$objectType]    =   $connector->getObjectDescription($objectType);
        }
        //====================================================================//
        // Render Connector Profile Page
        return $this->render("@SplashAdmin/Profile/list.html.twig", array(
            'action'    => 'list',
            'admin'     =>  $this->admin,
            "profile"   =>  $connector->getProfile(),
            "infos"     =>  $informations,
            "config"    =>  Splash::configuration(),
            "results"   =>  $results,
            "selftest"  =>  $logSelfTest,
            "ping"      =>  $logPingTest,
            "connect"   =>  $logConnectTest,
            "objects"   =>  $objects,
        ));
    }
    
    /**
     * Show action.
     *
     * @param null|mixed $objectId
     *
     * @return Response
     */
    public function showAction($objectId = null)
    {
        //====================================================================//
        // Setup Connector
        $connector  =   $this->getConnector();
        //====================================================================//
        // Render Connector Profile Page
        return $this->render("@SplashAdmin/Profile/show.html.twig", array(
            'action'    => 'list',
            "profile"   =>  $connector->getProfile(),
            "object"    =>  $connector->getObjectDescription($objectId),
            "fields"    =>  $connector->getObjectFields($objectId),
            "log"       =>  Splash::log()->GetHtmlLog(true),
        ));
    }
}
