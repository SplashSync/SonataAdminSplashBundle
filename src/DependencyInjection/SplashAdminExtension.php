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

namespace Splash\Admin\DependencyInjection;

use Sonata\AdminBundle\Datagrid\Pager;
use Splash\Admin\Model\ObjectsManager;
use stdClass;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\PrependExtensionInterface;
use Symfony\Component\DependencyInjection\Loader;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;

/**
 * This is the class that loads and manages your bundle configuration
 *
 * To learn more see {@link http://symfony.com/doc/current/cookbook/bundles/extension.html}
 */
class SplashAdminExtension extends Extension implements PrependExtensionInterface
{
    const TYPE_CONFIG = "configuration";
    const TYPE_PROFILE = "profile";
    const TYPE_OBJECTS = "objects";
    const TYPE_WIDGETS = "widgets";

    /**
     * @var array
     */
    protected $formTypeTemplates = array(
        '@SplashAdmin/Forms/burgov.html.twig',
        '@SplashAdmin/Forms/price.html.twig',
        '@SplashAdmin/Forms/file.html.twig',
        '@SplashAdmin/Forms/image.html.twig',
        '@SplashAdmin/Forms/objectid.html.twig',
    );

    /**
     * @var ContainerBuilder
     */
    private ContainerBuilder $container;

    /**
     * {@inheritdoc}
     */
    public function load(array $configs, ContainerBuilder $container): void
    {
        //====================================================================//
        // Store Container
        $this->container = $container;
        //====================================================================//
        // Load Bundle Services
        $loader = new Loader\YamlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));
        $loader->load('services.yml');
        //====================================================================//
        // Load Splash Core Bundle Configuration
        /** @var array $config */
        $config = $container->getParameter('splash');
        //====================================================================//
        // Add Available Connections to Sonata Admin
        foreach ($config["connections"] as $tagId => $connection) {
            $this->addAdminService(self::TYPE_PROFILE, $tagId, $connection["name"]);
            $this->addAdminService(self::TYPE_CONFIG, $tagId, $connection["name"]);
            $this->addAdminService(self::TYPE_OBJECTS, $tagId, $connection["name"]);
            $this->addAdminService(self::TYPE_WIDGETS, $tagId, $connection["name"]);
        }
    }

    /**
     * {@inheritDoc}
     */
    public function prepend(ContainerBuilder $container): void
    {
        $this->configureTwigBundle($container);
    }

    /**
     * Add Form Fields to Twig Form Themes
     *
     * @param ContainerBuilder $container
     *
     * @return void
     */
    private function configureTwigBundle(ContainerBuilder $container): void
    {
        foreach (array_keys($container->getExtensions()) as $name) {
            switch ($name) {
                case 'twig':
                    $container->prependExtensionConfig(
                        $name,
                        array('form_themes' => $this->formTypeTemplates)
                    );

                    break;
            }
        }
    }

    /**
     * Add Admin Service to Container
     *
     * @param string $adminType
     * @param string $tagId
     * @param string $name
     *
     * @return void
     */
    private function addAdminService(string $adminType, string $tagId, string $name): void
    {
        //====================================================================//
        // Create Sonata Admin Service
        $this->container
            ->register(
                'splash.admin.'.$tagId.'.'.$adminType,
                "Splash\\Admin\\Admin\\".ucwords($adminType)."Admin"
            )
            ->setAutowired(true)
            ->addMethodCall(
                "setModelManager",
                array(new Reference(ObjectsManager::class))
            )
            ->setArgument("\$serverId", $tagId)
            ->setArgument("\$adminType", $adminType)
            //====================================================================//
            // Build Service Tags Array
            ->addTag("sonata.admin", array(
                "model_class" => stdClass::class,
                "manager_type" => "orm",
                "controller" => "Splash\\Admin\\Controller\\".ucwords($adminType)."Controller",
                "pager_type" => Pager::TYPE_SIMPLE,
                "group" => $name,
                "label" => ucwords($adminType),
                "icon" => '<span class="fa fa-server"></span>',
            ))
        ;
    }
}
