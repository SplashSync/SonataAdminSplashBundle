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
use Splash\Admin\Admin\ObjectsAdmin;
use Splash\Admin\Model\ObjectManagerAwareTrait;
use Splash\Core\SplashCore as Splash;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Description of ObjectCRUDController.
 *
 * @author nanard33
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
     * @param Request $request
     *
     * @return Response
     */
    public function switchAction(Request $request)
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
     * List action.
     *
     * @return Response
     */
    public function listAction()
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

        $this->getObjectsManager()->setObjectType($objectType);
        //====================================================================//
        // Prepare List Parameters
        $listPage = (int) $this->getRequest()->get("page", 1);
        $listParams = array(
            "max" => self::LIST_MAX,
            "offset" => (($listPage - 1) * self::LIST_MAX),
        );
        //====================================================================//
        // Read Object List
        $objectsList = $this->getObjectsManager()->findBy($objectType, $listParams);
        $objectsMeta = isset($objectsList["meta"]) ? $objectsList["meta"] : array();
        unset($objectsList['meta']);
        //====================================================================//
        // Render Connector Profile Page
        return $this->render('@SplashAdmin/Objects/list.html.twig', array(
            'action' => 'list',
            'admin' => $this->admin,
            'log' => Splash::log()->GetHtmlLog(true),
            'ObjectType' => $objectType,
            'objects' => $this->getObjectsManager()->getObjectsDefinition(),
            'fields' => $this->getObjectsManager()->getObjectFields(),
            'list' => $objectsList,
            'meta' => $objectsMeta,
        ));
    }

    /**
     * Show action.
     *
     * @param string $objectId
     *
     * @return Response
     */
    public function showAction($objectId = null)
    {
        //====================================================================//
        // Detect Current Object Type
        $this->getObjectsManager()->setObjectType($this->admin->getObjectType());
        $this->getObjectsManager()->setShowMode();

        try {
            //====================================================================//
            // Base Admin Action
            return parent::showAction($objectId);
        } catch (NotFoundHttpException $ex) {
            //====================================================================//
            // Redirect to Objects List
            $this->addFlash("warning", "Object ".$objectId." was not found on this Server");

            return $this->listAction();
        }
    }

    /**
     * Edit action.
     *
     * @param string $objectId
     *
     * @return Response
     */
    public function editAction($objectId = null)
    {
        //====================================================================//
        // Detect Current Object Type
        $this->getObjectsManager()->setObjectType($this->admin->getObjectType());

        try {
            //====================================================================//
            // Base Admin Action
            $response = parent::editAction($objectId);
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
            //====================================================================//
            // Redirect to Objects List
            $this->addFlash("warning", "Object ".$objectId." was not found on this Server");

            return $this->listAction();
        }

        //====================================================================//
        // Return Standard Response
        return $response;
    }

    /**
     * Create action.
     *
     * @return Response
     */
    public function createAction()
    {
        //====================================================================//
        // Detect Current Object Type
        $this->getObjectsManager()->setObjectType($this->admin->getObjectType());
        //====================================================================//
        // Base Admin Action
        return parent::createAction();
    }

    /**
     * Delete action.
     *
     * @param string $objectId
     *
     * @return Response
     */
    public function deleteAction($objectId = null)
    {
        //====================================================================//
        // Detect Current Object Type
        $this->getObjectsManager()->setObjectType($this->admin->getObjectType());
        //====================================================================//
        // Base Admin Action
        return parent::deleteAction($objectId);
    }

    /**
     * Show Image action.
     *
     * @param string $path
     * @param string $md5
     *
     * @return Response
     */
    public function imageAction(string $path, string $md5)
    {
        //====================================================================//
        // Detect Current Object Type
        $this->getObjectsManager()->setObjectType($this->admin->getObjectType());
        //====================================================================//
        // Load File From Connnector
        $filePath = (string) base64_decode($path, true);
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

        return new Response(base64_decode($fileArray['raw'], true), 200, $headers);
    }

    /**
     * File action.
     *
     * @param string $path
     * @param string $md5
     *
     * @return Response
     */
    public function fileAction(string $path, string $md5)
    {
        //====================================================================//
        // Detect Current Object Type
        $this->getObjectsManager()->setObjectType($this->admin->getObjectType());
        //====================================================================//
        // Load File From Connnector
        $fileArray = $this->getObjectsManager()
            ->getConnector()
            ->getFile((string) base64_decode($path, true), $md5);
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

        return new Response(base64_decode($fileArray['raw'], true), 200, $headers);
    }
}
