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

namespace Splash\Admin\Services;

use Psr\Cache\InvalidArgumentException;
use Splash\Admin\Fields\FormHelper;
use Splash\Bundle\Models\AbstractConnector;
use Splash\Bundle\Services\ConnectorsManager;
use Splash\Widgets\Entity\Widget;
use Splash\Widgets\Models\Interfaces\WidgetProviderInterface;
use Splash\Widgets\Models\Traits\ParametersTrait;
use Splash\Widgets\Services\FactoryService;
use Symfony\Component\Cache\Adapter\FilesystemAdapter;
use Symfony\Component\Form\FormBuilderInterface;

/**
 *    Splash Admin Widgets Factory Service
 */
class WidgetFactoryService implements WidgetProviderInterface
{
    use ParametersTrait;

    const SERVICE = 'splash.admin.widget.factory';

    const ORIGIN = "<i class='fa fa-github text-success' aria-hidden='true'>&nbsp;</i>&nbsp;";

    const OPTIONS_CACHE_KEY = 'splash.admin.widget.options.';
    const PARAMS_CACHE_KEY = 'splash.admin.widget.parameters.';

    /**
     * WidgetFactory Service.
     *
     * @var FactoryService
     */
    private FactoryService $factory;

    /**
     * Connectors Manager Service.
     *
     * @var ConnectorsManager
     */
    private ConnectorsManager $manager;

    /**
     * Symfony cache.
     *
     * @var FilesystemAdapter
     */
    private FilesystemAdapter $cache;

    //====================================================================//
    //  CONSTRUCTOR
    //====================================================================//

    /**
     * Service Constructor
     *
     * @param FactoryService    $widgetFactory
     * @param ConnectorsManager $connectorsManager
     */
    public function __construct(FactoryService $widgetFactory, ConnectorsManager $connectorsManager)
    {
        //====================================================================//
        // Link to WidgetFactory Service
        $this->factory = $widgetFactory;
        //====================================================================//
        // Link to Connectors Manager Service
        $this->manager = $connectorsManager;
        //====================================================================//
        // Link to Sf Cache
        $this->cache = new FilesystemAdapter();
    }

    /**
     * Read Widget Contents
     *
     * @param string     $type       Widgets Type Identifier
     * @param null|array $parameters Widget Parameters
     *
     * @throws InvalidArgumentException
     *
     * @return null|Widget
     */
    public function getWidget(string $type, ?array $parameters = array()): ?Widget
    {
        //====================================================================//
        // Build Widget Definition
        $this->buildWidgetDefinition($type);
        //====================================================================//
        // Merge Input Parameter with Cached
        $mergedParameters = array_replace_recursive(
            $this->getWidgetParameters($type),
            is_null($parameters) ? array() : $parameters
        );
        //====================================================================//
        // Add Dates from Presets
        $datedParameters = self::addDatesPresets($mergedParameters);
        //====================================================================//
        // Load Widget Contents
        $this->addWidgetContents(
            $type,
            $datedParameters
        );
        //====================================================================//
        // Return Splash Widget
        return $this->factory->getWidget();
    }

    /**
     * Return Widget Options Array
     *
     * @param string $type Widgets Type Identifier
     *
     * @throws InvalidArgumentException
     *
     * @return array
     */
    public function getWidgetOptions(string $type): array
    {
        //====================================================================//
        // If Widget Options are in Cache
        $cacheItem = $this->cache->getItem(self::OPTIONS_CACHE_KEY.base64_encode($type));
        if ($cacheItem->isHit()) {
            /** @var array $options */
            $options = $cacheItem->get();
            //====================================================================//
            // Force Widget Footer Rendering
            $options['Footer'] = true;

            return $options;
        }
        //====================================================================//
        // Default Widget Options

        $options = Widget::getDefaultOptions();
        $options['Editable'] = true;

        return $options;
    }

    /**
     * Update Widget Options Array
     *
     * @param string $type    Widgets Type Identifier
     * @param array  $options Updated Options
     *
     * @throws InvalidArgumentException
     *
     * @return true
     */
    public function setWidgetOptions(string $type, array $options): bool
    {
        $cacheItem = $this->cache->getItem(self::OPTIONS_CACHE_KEY.base64_encode($type));
        $cacheItem->set($options);
        $this->cache->save($cacheItem);

        return true;
    }

    /**
     * Return Widget Parameters Array
     *
     * @param string $type Widgets Type Identifier
     *
     * @throws InvalidArgumentException
     *
     * @return array
     */
    public function getWidgetParameters(string $type): array
    {
        //====================================================================//
        // If Widget Options are in Cache
        $cacheItem = $this->cache->getItem(self::PARAMS_CACHE_KEY.base64_encode($type));
        if ($cacheItem->isHit()) {
            /** @var null|array $parameters */
            $parameters = $cacheItem->get();

            return is_array($parameters) ? $parameters : array();
        }
        //====================================================================//
        // Default Widget Options
        return array();
    }

    /**
     * Update Widget Parameters Array
     *
     * @param string $type       Widgets Type Identifier
     * @param array  $parameters Updated Parameters
     *
     * @throws InvalidArgumentException
     *
     * @return true
     */
    public function setWidgetParameters(string $type, array $parameters): bool
    {
        $cacheItem = $this->cache->getItem(self::PARAMS_CACHE_KEY.base64_encode($type));
        $cacheItem->set($parameters);
        $this->cache->save($cacheItem);

        return true;
    }

    /**
     * Return Widget Parameters Fields Array
     *
     * @param FormBuilderInterface $builder
     * @param string               $type    Widgets Type Identifier
     */
    public function populateWidgetForm(FormBuilderInterface $builder, string $type): void
    {
        //==============================================================================
        // Verify Parameter Fields are Available
        $fields = $this->getWidgetDefinition($type);
        if (empty($fields['parameters'])) {
            return;
        }
        //==============================================================================
        // Add Parameters to Widget Form
        foreach ($fields['parameters'] as $field) {
            //====================================================================//
            // Add Single Fields to Form
            $builder->add(
                $field['id'],
                FormHelper::formType($field),
                FormHelper::formOptions($field)
            );
        }
    }

    /**
     * Get Widget Connector Service
     *
     * @param string $type
     *
     * @return null|AbstractConnector
     */
    private function getWidgetConnector(string $type): ?AbstractConnector
    {
        //====================================================================//
        // Decode Widget Type
        list($widgetType, $webserviceId) = explode('@', $type);
        if (empty($widgetType) || empty($webserviceId)) {
            return null;
        }
        //==============================================================================
        // Get Target Connector
        $serverId = $this->manager->identify($webserviceId);
        if (empty($serverId)) {
            return null;
        }
        $connector = $this->manager->get($serverId);
        if (empty($connector)) {
            return null;
        }
        //==============================================================================
        // Check Widget Exist
        if (!in_array($widgetType, $connector->getAvailableWidgets(), true)) {
            return null;
        }

        return $connector;
    }

    /**
     * Read Widgets Definition from Connector
     *
     * @param string $type
     *
     * @return null|array
     */
    private function getWidgetDefinition(string $type): ?array
    {
        //==============================================================================
        // Get Target Connector
        $connector = $this->getWidgetConnector($type);
        //==============================================================================
        // Check Widget Exist
        if (is_null($connector)) {
            return null;
        }
        //====================================================================//
        // Decode Widget Type
        list($widgetType) = explode('@', $type);
        //==============================================================================
        // Get Target Widget Description
        return $connector->getWidgetDescription($widgetType);
    }

    /**
     * Widgets Listing
     *
     * @param string $type
     *
     * @throws InvalidArgumentException
     *
     * @return FactoryService
     */
    private function buildWidgetDefinition(string $type): FactoryService
    {
        //==============================================================================
        // Get Target Connector
        $connector = $this->getWidgetConnector($type);
        //==============================================================================
        // Check Widget Exist
        if (is_null($connector)) {
            return $this->factory;
        }
        //====================================================================//
        // Decode Widget Type
        list($widgetType, $webserviceId) = explode('@', $type);
        //==============================================================================
        // Get Target Widget Description
        $description = $connector->getWidgetDescription($widgetType);
        //==============================================================================
        // Build Widget Definition
        /** @var Widget $widget */
        $widget = $this->factory->Create();
        $widget->setService(WidgetFactoryService::SERVICE);
        $widget->setType($type);
        $widget->setName($description['name']);
        $widget->setDescription($description['description']);
        $widget->setOrigin(self::ORIGIN.$connector->getSplashType());
        $widget->setOptions($this->getWidgetOptions($type));
        $widget->setExtras(array(
            'WidgetType' => $widgetType,
            'WebserviceId' => $webserviceId,
        ))
        ;

        return $this->factory;
    }

    /**
     * Get & Add Contents to Current Widget
     *
     * @param string $type
     * @param array  $parameters
     *
     * @return FactoryService
     */
    private function addWidgetContents(string $type, array $parameters = array()): FactoryService
    {
        //==============================================================================
        // Get Target Connector
        $connector = $this->getWidgetConnector($type);
        //==============================================================================
        // Check Widget Exist
        if (is_null($connector)) {
            return $this->factory;
        }
        //====================================================================//
        // Decode Widget Type
        list($widgetType) = explode('@', $type);
        //==============================================================================
        // Get Target Widget Contents
        $widgetContents = $connector->getWidgetContents($widgetType, $parameters);
        //==============================================================================
        // No Contents => Show Error Message
        if (empty($widgetContents)) {
            $this->factory->buildErrorWidget(self::SERVICE, $type, 'Server Returned Empty Contents.');

            return $this->factory;
        }
        //==============================================================================
        // Import Widget Contents
        /** @var Widget $widget */
        $widget = $this->factory;
        $widget->setContents($widgetContents);
        //==============================================================================
        // Add Blocks to Widget
        if (isset($widgetContents['blocks'])) {
            $this->factory->addBlocks($widgetContents['blocks']);
        }

        return $this->factory;
    }
}
