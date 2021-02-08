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

namespace Splash\Admin\Services;

use Splash\Admin\Fields\FormHelper;
use Splash\Bundle\Models\AbstractConnector;
use Splash\Bundle\Services\ConnectorsManager;
use Splash\Widgets\Entity\Widget;
use Splash\Widgets\Models\Interfaces\WidgetProviderInterface;
use Splash\Widgets\Models\Traits\ParametersTrait;
use Splash\Widgets\Services\FactoryService;
use Symfony\Component\Cache\Simple\FilesystemCache;
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
    private $factory;

    /**
     * Connectors Manager Service.
     *
     * @var ConnectorsManager
     */
    private $manager;

    /**
     * Symfony cache.
     *
     * @var FilesystemCache
     */
    private $cache;

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
        $this->cache = new FilesystemCache();
    }

    /**
     * Read Widget Contents
     *
     * @param string     $connectorWidgetType Widgets Type Identifier
     * @param null|array $parameters          Widget Parameters
     *
     * @return null|Widget
     */
    public function getWidget(string $connectorWidgetType, ?array $parameters = array()): ?Widget
    {
        //====================================================================//
        // Build Widget Definition
        $this->buildWidgetDefinition($connectorWidgetType);
        //====================================================================//
        // Merge Input Parameter with Cached
        $mergedParameters = array_replace_recursive(
            $this->getWidgetParameters($connectorWidgetType),
            is_null($parameters) ? array() : $parameters
        );
        //====================================================================//
        // Add Dates from Presets
        $datedParameters = self::addDatesPresets($mergedParameters);
        //====================================================================//
        // Load Widget Contents
        $this->addWidgetContents(
            $connectorWidgetType,
            $datedParameters
        );
        //====================================================================//
        // Return Splash Widget
        return $this->factory->getWidget();
    }

    /**
     * Return Widget Options Array
     *
     * @param string $connectorWidgetType Widgets Type Identifier
     *
     * @return array
     */
    public function getWidgetOptions(string $connectorWidgetType): array
    {
        //====================================================================//
        // If Widget Options are in Cache
        if ($this->cache->has(self::OPTIONS_CACHE_KEY.base64_encode($connectorWidgetType))) {
            $options = $this->cache->get(self::OPTIONS_CACHE_KEY.base64_encode($connectorWidgetType));
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
     * @param string $connectorWidgetType Widgets Type Identifier
     * @param array  $options             Updated Options
     *
     * @return true
     */
    public function setWidgetOptions(string $connectorWidgetType, array $options): bool
    {
        $this->cache->set(self::OPTIONS_CACHE_KEY.base64_encode($connectorWidgetType), $options);

        return true;
    }

    /**
     * Return Widget Parameters Array
     *
     * @param string $connectorWidgetType Widgets Type Identifier
     *
     * @return array
     */
    public function getWidgetParameters(string $connectorWidgetType): array
    {
        //====================================================================//
        // If Widget Parameters are in Cache
        if ($this->cache->has(self::PARAMS_CACHE_KEY.base64_encode($connectorWidgetType))) {
            return $this->cache->get(self::PARAMS_CACHE_KEY.base64_encode($connectorWidgetType));
        }
        //====================================================================//
        // Default Widget Options
        return array();
    }

    /**
     * Update Widget Parameters Array
     *
     * @param string $connectorWidgetType Widgets Type Identifier
     * @param array  $parameters          Updated Parameters
     *
     * @return true
     */
    public function setWidgetParameters(string $connectorWidgetType, array $parameters): bool
    {
        $this->cache->set(self::PARAMS_CACHE_KEY.base64_encode($connectorWidgetType), $parameters);

        return true;
    }

    /**
     * Return Widget Parameters Fields Array
     *
     * @param FormBuilderInterface $builder
     * @param string               $connectorWidgetType Widgets Type Identifier
     */
    public function populateWidgetForm(FormBuilderInterface $builder, string $connectorWidgetType): void
    {
        //==============================================================================
        // Verify Parameter Fields are Available
        $fields = $this->getWidgetDefinition($connectorWidgetType);
        if (!isset($fields['parameters']) || empty($fields['parameters'])) {
            return;
        }
        //==============================================================================
        // Add Parameters to Widget Form
        foreach ($fields['parameters'] as $field) {
            //====================================================================//
            // Add Single Fields to Form
            $builder->add(
                $field->id,
                FormHelper::formType($field),
                FormHelper::formOptions($field)
            );
        }
    }

    /**
     * Get Widget Connector Service
     *
     * @param string $connectorWidgetType
     *
     * @return null|AbstractConnector
     */
    private function getWidgetConnector(string $connectorWidgetType)
    {
        //====================================================================//
        // Decode Widget Type
        list($widgetType, $webserviceId) = explode('@', $connectorWidgetType);
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
     * @param string $connectorWidgetType
     *
     * @return null|array
     */
    private function getWidgetDefinition(string $connectorWidgetType)
    {
        //==============================================================================
        // Get Target Connector
        $connector = $this->getWidgetConnector($connectorWidgetType);
        //==============================================================================
        // Check Widget Exist
        if (is_null($connector)) {
            return null;
        }
        //====================================================================//
        // Decode Widget Type
        list($widgetType) = explode('@', $connectorWidgetType);
        //==============================================================================
        // Get Target Widget Description
        return $connector->getWidgetDescription($widgetType);
    }

    /**
     * Widgets Listing
     *
     * @param string $connectorWidgetType
     *
     * @return FactoryService
     */
    private function buildWidgetDefinition(string $connectorWidgetType)
    {
        //==============================================================================
        // Get Target Connector
        $connector = $this->getWidgetConnector($connectorWidgetType);
        //==============================================================================
        // Check Widget Exist
        if (is_null($connector)) {
            return $this->factory;
        }
        //====================================================================//
        // Decode Widget Type
        list($widgetType, $webserviceId) = explode('@', $connectorWidgetType);
        //==============================================================================
        // Get Target Widget Description
        $description = $connector->getWidgetDescription($widgetType);
        //==============================================================================
        // Build Widget Definition
        /** @var Widget $widget */
        $widget = $this->factory->Create();
        $widget->setService(WidgetFactoryService::SERVICE);
        $widget->setType($connectorWidgetType);
        $widget->setName($description['name']);
        $widget->setDescription($description['description']);
        $widget->setOrigin(self::ORIGIN.$connector->getSplashType());
        $widget->setOptions($this->getWidgetOptions($connectorWidgetType));
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
     * @param string $connectorWidgetType
     * @param array  $parameters
     *
     * @return FactoryService
     */
    private function addWidgetContents(string $connectorWidgetType, $parameters = array())
    {
        //==============================================================================
        // Get Target Connector
        $connector = $this->getWidgetConnector($connectorWidgetType);
        //==============================================================================
        // Check Widget Exist
        if (is_null($connector)) {
            return $this->factory;
        }
        //====================================================================//
        // Decode Widget Type
        list($widgetType) = explode('@', $connectorWidgetType);
        //==============================================================================
        // Get Target Widget Contents
        $widgetContents = $connector->getWidgetContents($widgetType, $parameters);
        //==============================================================================
        // No Contents => Show Error Message
        if (empty($widgetContents)) {
            $this->factory->buildErrorWidget(self::SERVICE, $connectorWidgetType, 'Server Returned Empty Contents.');

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
