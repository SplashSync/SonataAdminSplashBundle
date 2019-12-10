<?php

/*
 *  This file is part of SplashSync Project.
 *
 *  Copyright (C) 2015-2019 Splash Sync  <www.splashsync.com>
 *
 *  This program is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 *
 *  For the full copyright and license information, please view the LICENSE
 *  file that was distributed with this source code.
 */

namespace Splash\Admin\DependencyInjection;

use ArrayObject;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\PrependExtensionInterface;
use Symfony\Component\DependencyInjection\Loader;
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
    private $container;

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
        $config = $container->getParameter('splash');

        //====================================================================//
        // Add Availables Connections to Sonata Admin
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
     * @param string $type
     * @param string $tagId
     * @param string $name
     *
     * @return void
     */
    private function addAdminService(string $type, string $tagId, string $name): void
    {
        //====================================================================//
        // Build Service Tags Array
        $tags = array(
            "manager_type" => "orm",
            "group" => $name,
            "label" => ucwords($type),
            "icon" => '<span class="fa fa-server"></span>',
        );
        //====================================================================//
        // Build Admin Class Name
        $adminClass = "Splash\\Admin\\Admin\\".ucwords($type)."Admin";
        $controllerClass = "Splash\\Admin\\Controller\\".ucwords($type)."Controller";

        //====================================================================//
        // Build Service Configurations
        $args = array(
            null,
            ArrayObject::class,     // Data Type
            $controllerClass,       // Controller Class Name
            $tagId,                    // Splash Server Id
            $type,                   // Admin Type Name
        );

        //====================================================================//
        // Create Sonata Admin Service
        $this->container
            ->register('splash.admin.'.$tagId.'.'.$type, $adminClass)
            ->addTag("sonata.admin", $tags)
            ->setArguments($args);
    }
}
