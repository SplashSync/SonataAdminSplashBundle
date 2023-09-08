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

use Exception;
use Sonata\AdminBundle\Controller\CRUDController;
use Splash\Admin\Admin\ObjectsAdmin;
use Splash\Admin\Model\ObjectManagerAwareTrait;
use Splash\Core\SplashCore as Splash;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Description of ObjectCRUDController.
 */
class ObjectsController extends CRUDController
{
    use ObjectManagerAwareTrait;

    /**
     * @var int
     */
    const LIST_MAX = 10;

    /**
     * @var ObjectsAdmin
     */
    protected $admin;

    /**
     * Switch Between Object Types
     *
     * @throws Exception
     */
    public function switchAction(Request $request): Response
    {
        $objectType = $request->get('ObjectType');
        $objectTypes = $this->getObjectsManager()->getObjects();
        $session = $request->getSession();
        if ($objectType && in_array($objectType, $objectTypes, true)) {
            $session->set('ObjectType', $objectType);
        }

        return $this->redirectToList();
    }

    /**
     * {@inheritDoc}
     *
     * @throws Exception
     */
    public function listAction(Request $request): Response
    {
        //====================================================================//
        // Detect Current Object Type
        /** @var class-string $objectType */
        $objectType = $this->admin->getObjectType();
        if (empty($objectType)) {
            //====================================================================//
            // Add Error To Splash Log
            Splash::log()->err("No Object Type found on this Server.");

            //====================================================================//
            // Render Connector Profile Page
            return $this->render('@SplashAdmin/Objects/empty.html.twig', array(
                'action' => 'list',
                'admin' => $this->admin,
                'log' => Splash::log()->GetHtmlLog(true),
            ));
        }
        //====================================================================//
        // Prepare DataGrid & Pager
        $this->getObjectsManager()->setObjectType($objectType);
        $dataGrid = $this->admin->getDatagrid();
        $formView = $dataGrid->getForm()->createView();

        //====================================================================//
        // Render Connector Profile Page
        return $this->render('@SplashAdmin/Objects/list.html.twig', array(
            'action' => 'list',
            'admin' => $this->admin,
            'datagrid' => $dataGrid,
            'form' => $formView,
            'log' => Splash::log()->GetHtmlLog(true),
            'objects' => $this->getObjectsManager()->getObjectsDefinition(),
            'fields' => $this->getObjectsManager()->getObjectFields(),
            'csrf_token' => $this->getCsrfToken('sonata.batch')
        ));
    }

    /**
     * {@inheritDoc}
     *
     * @throws Exception
     */
    public function showAction(Request $request): Response
    {
        //====================================================================//
        // Detect Current Object Type
        $this->getObjectsManager()->setObjectType($this->admin->getObjectType());
        $this->getObjectsManager()->setShowMode();

        try {
            //====================================================================//
            // Base Admin Action
            return parent::showAction($request);
        } catch (NotFoundHttpException $ex) {
            /** @var null|string $objectId */
            $objectId = $request->get("id");
            //====================================================================//
            // Redirect to Objects List
            $this->addFlash(
                "warning",
                sprintf("Object %s was not found on this Server", $objectId)
            );

            return $this->listAction($request);
        }
    }

    /**
     * {@inheritDoc}
     *
     * @throws Exception
     */
    public function editAction(Request $request): Response
    {
        //====================================================================//
        // Detect Current Object Type
        $this->getObjectsManager()->setObjectType($this->admin->getObjectType());

        try {
            //====================================================================//
            // Base Admin Action
            $response = parent::editAction($request);
        } catch (NotFoundHttpException $ex) {
            //====================================================================//
            // Detect Object Id Changed
            $newObjectId = $this->getObjectsManager()->getNewObjectId();
            if ($newObjectId) {
                //====================================================================//
                // Redirect to New Object Edit Url
                $redirectUrl = $this->admin->generateUrl("edit", array("id" => $newObjectId));

                return $this->redirect($redirectUrl);
            }
            /** @var null|string $objectId */
            $objectId = $request->get("id");
            //====================================================================//
            // Redirect to Objects List
            $this->addFlash(
                "warning",
                sprintf("Object %s was not found on this Server", $objectId)
            );

            return $this->listAction($request);
        }

        //====================================================================//
        // Return Standard Response
        return $response;
    }

    /**
     * Create action.
     *
     * @throws Exception
     */
    public function createAction(Request $request): Response
    {
        //====================================================================//
        // Detect Current Object Type
        $this->getObjectsManager()->setObjectType($this->admin->getObjectType());

        //====================================================================//
        // Base Admin Action
        return parent::createAction($request);
    }

    /**
     * Delete action.
     *
     * @throws Exception
     */
    public function deleteAction(Request $request): Response
    {
        //====================================================================//
        // Detect Current Object Type
        $this->getObjectsManager()->setObjectType($this->admin->getObjectType());

        //====================================================================//
        // Base Admin Action
        return parent::deleteAction($request);
    }

    /**
     * Show Image action.
     *
     * @param string $path
     * @param string $md5
     *
     * @throws Exception
     *
     * @return Response
     */
    public function imageAction(string $path, string $md5): Response
    {
        //====================================================================//
        // Detect Current Object Type
        $this->getObjectsManager()->setObjectType($this->admin->getObjectType());
        //====================================================================//
        // Load File From Connector
        $filePath = (string) base64_decode(html_entity_decode($path), true);
        $fileArray = $this->getObjectsManager()
            ->getConnector()
            ->getFile($filePath, $md5);
        if (!$fileArray) {
            print Splash::log()->getHtmlLog();

            throw $this->createNotFoundException(
                sprintf('unable to find the file with path: %s', $filePath)
            );
        }
        //==============================================================================
        // Return Image Response
        $headers = array(
            'Content-Type' => 'image/png',
            'Content-Disposition' => 'inline; filename="'.$fileArray['filename'].'"', );

        return new Response((string) base64_decode($fileArray['raw'], true), 200, $headers);
    }

    /**
     * File action.
     *
     * @param string $path
     * @param string $md5
     *
     * @throws Exception
     *
     * @return Response
     */
    public function fileAction(string $path, string $md5): Response
    {
        //====================================================================//
        // Detect Current Object Type
        $this->getObjectsManager()->setObjectType($this->admin->getObjectType());
        //====================================================================//
        // Load File From Connector
        $fileArray = $this->getObjectsManager()
            ->getConnector()
            ->getFile((string) base64_decode(html_entity_decode($path), true), $md5);
        if (!$fileArray) {
            print Splash::log()->getHtmlLog();

            throw $this->createNotFoundException(
                sprintf('unable to find the file with path: %s', $path)
            );
        }
        //==============================================================================
        // Return Image Response
        $headers = array(
            'Content-Type' => 'application/force-download',
            'Content-Disposition' => 'inline; filename="'.$fileArray['filename'].'"', );

        return new Response((string) base64_decode($fileArray['raw'], true), 200, $headers);
    }
}
