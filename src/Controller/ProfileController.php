<?php

/**
 * This file is part of SplashSync Project.
 *
 * Copyright (C) Splash Sync <www.splashsync.com>
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 * 
 * @author Bernard Paquier <contact@splashsync.com>
 */

namespace App\ExplorerBundle\Controller;

use ArrayObject;

use Symfony\Component\HttpFoundation\Response;

use Sonata\AdminBundle\Controller\CRUDController;

use Splash\Core\SplashCore as Splash;
use Splash\Bundle\Models\ConnectorInterface;

/**
 * Description of ObjectCRUDController
 *
 * @author nanard33
 */
class ProfileController extends CRUDController {
    
    /**
     * List action.
     *
     * @throws AccessDeniedException If access is not granted
     *
     * @return Response
     */
    public function listAction()
    {
        $Results = array();
        //====================================================================//
        // Setup Connector
        $Connector  =   $this->admin->getModelManager()->getConnector();
        //====================================================================//
        // Execute Splash Self-Test
        $Results['selftest'] = $Connector->selfTest();
        if ($Results['selftest']) {
            Splash::log()->msg("Self-Test Passed");
        }
        $SelfTest_Log = Splash::log()->GetHtmlLog(true);
        //====================================================================//
        // Execute Splash Ping Test
        $Results['ping']    = $Connector->ping();
        $PingTest_Log       = Splash::log()->GetHtmlLog(true);
        //====================================================================//
        // Execute Splash Connect Test
        $Results['connect'] = $Connector->connect();
        $ConnectTest_Log    = Splash::log()->GetHtmlLog(true);
        //====================================================================//
        // Load Connector Informations
        $Informations    = array();
        if ($Results['ping'] && $Results['connect']) {
            $Informations    = $Connector->informations(new ArrayObject(array()));
        }
        //====================================================================//
        // Load Objects Informations
        $Objects   =   array();
        foreach ($Connector->getAvailableObjects() as $ObjectType) {
            $Objects[$ObjectType]    =   $Connector->getObjectDescription($ObjectType);            
        }
        //====================================================================//
        // Render Connector Profile Page
        return $this->render("@AppExplorer/Profile/list.html.twig", array(
            'action'    => 'list',
            'admin'     =>  $this->admin,
            "profile"   =>  $Connector->getProfile(),
            "infos"     =>  $Informations,
            "config"    =>  Splash::configuration(),
            "results"   =>  $Results,
            "selftest"  =>  $SelfTest_Log,
            "ping"      =>  $PingTest_Log,
            "connect"   =>  $ConnectTest_Log,
            "objects"   =>  $Objects,
        ));
    }
    
    /**
     * Show action.
     *
     * @throws AccessDeniedException If access is not granted
     *
     * @return Response
     */
    public function showAction($id = null)
    {
        //====================================================================//
        // Setup Connector
        $Connector  =   $this->admin->getModelManager()->getConnector();
        //====================================================================//
        // Render Connector Profile Page
        return $this->render("@AppExplorer/Profile/show.html.twig", array(
            'action'    => 'list',
            "profile"   =>  $Connector->getProfile(),
            "object"    =>  $Connector->getObjectDescription($id),
            "fields"    =>  $Connector->getObjectFields($id),
            "log"       =>  Splash::log()->GetHtmlLog(true),
        ));
    }    
}
