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

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Form\FormView;
use Symfony\Component\Form\FormRenderer;

use Symfony\Component\Form\Extension\Core\Type\SubmitType;

use Sonata\AdminBundle\Controller\CRUDController;

/**
 * Sonata Admin CRUD Controller for Splash Connectors Configurations
 */
class ConfigurationController extends CRUDController {
    
    /**
     * List action.
     *
     * @throws AccessDeniedException If access is not granted
     *
     * @return Response
     */
    public function listAction(Request $request = null)
    {
        //====================================================================//
        // Setup Connector
        $Connector  =   $this->admin->getModelManager()->getConnector();
        //====================================================================//
        // Build Connector Edit Form
        $form = $this->createForm(
                $Connector->getFormBuilderName(), 
                $Connector->getConfiguration()
            );  
        //====================================================================//
        // Add Submit Button
        $form->add('submit', SubmitType::class, array(
            'label' => 'btn_update_and_edit_again',
            "attr"  =>  array(
                "class" => "btn btn-success pull-right",
                "style" => "margin-top:10px;" 
            ),
            'translation_domain' => 'SonataAdminBundle',
        ));
        //==============================================================================
        // Update Connector Configuration
        $form->handleRequest($request);
        if ( $form->isSubmitted() && $form->isValid() ) {
            $Connector->configure($Connector->getWebserviceId(), $form->getData());
            $Connector->updateConfiguration();
        }
        //==============================================================================
        // Create Form View
        $formView   = $form->createView();
        // set the theme for the current Admin Form
        $this->setFormTheme($formView, $this->admin->getFilterTheme());
        //====================================================================//
        // Render Connector Profile Page
        return $this->render("@AppExplorer/Config/list.html.twig", array(
            'action'        => 'list',
            'admin'         =>  $this->admin,
            "profile"       =>  $Connector->getProfile(),
            "connector"     =>  $Connector,
            "configuration" =>  $this->admin->getModelManager()->getConfiguration(),
            "form"          =>  $formView
        ));
    }
    
    /**
     * Sets the admin form theme to form view. Used for compatibility between Symfony versions.
     */
    private function setFormTheme(FormView $formView, array $theme = null)
    {
        $twig = $this->get('twig');
        $twig->getRuntime(FormRenderer::class)->setTheme($formView, $theme);
    }    
}
