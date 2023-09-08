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
use Exception;
use Sonata\AdminBundle\Controller\CRUDController;
use Splash\Admin\Model\ObjectManagerAwareTrait;
use Splash\Core\SplashCore as Splash;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormRenderer;
use Symfony\Component\Form\FormView;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Sonata Admin CRUD Controller for Splash Connectors Configurations.
 */
class ConfigurationController extends CRUDController
{
    use ObjectManagerAwareTrait;

    /**
     * List action.
     *
     * @param Request $request
     *
     * @throws Exception
     *
     * @return Response
     */
    public function listAction(Request $request = null): Response
    {
        //====================================================================//
        // Setup Connector
        $connector = $this->getConnector();
        //====================================================================//
        // Build Connector Edit Form
        $form = $this->createForm(
            $connector->getFormBuilderName(),
            $connector->getConfiguration()
        );

        //====================================================================//
        // Add Submit Button
        $form->add('submit', SubmitType::class, array(
            'label' => 'btn_update_and_edit_again',
            'attr' => array(
                'class' => 'btn btn-success pull-right',
                'style' => 'margin-top:10px;',
            ),
            'translation_domain' => 'SonataAdminBundle',
        ));
        //==============================================================================
        // Update Connector Configuration
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            /** @var array $data */
            $data = $form->getData();
            $connector->configure($connector->getSplashType(), $connector->getWebserviceId(), $data);
            $connector->updateConfiguration();
        }
        //==============================================================================
        // Fetch Connector Informations
        if ($connector->selftest()) {
            if ($connector->ping() && $connector->connect()) {
                $informations = $connector->informations(new ArrayObject(array()));
            }
        }
        //==============================================================================
        // Create Form View
        $formView = $form->createView();
        // set the theme for the current Admin Form
        $this->setFormTheme($formView, $this->admin->getFormTheme());

        //====================================================================//
        // Render Connector Profile Page
        return $this->render('@SplashAdmin/Config/list.html.twig', array(
            'action' => 'list',
            'admin' => $this->admin,
            'profile' => $connector->getProfile(),
            'log' => Splash::log()->GetHtmlLog(true),
            'connector' => $connector,
            'configuration' => $this->getObjectsManager()->getConfiguration(),
            'informations' => isset($informations) ? $informations : new ArrayObject(array()),
            'form' => $formView,
        ));
    }
}
