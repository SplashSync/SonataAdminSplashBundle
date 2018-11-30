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

namespace Splash\Admin\Services;

use Splash\Admin\Fields\FormHelper;
use Splash\Bundle\Models\AbstractConnector;
use Splash\Bundle\Services\ConnectorsManager;
use Splash\Widgets\Entity\Widget;
use Splash\Widgets\Models\Interfaces\WidgetProviderInterface;
use Splash\Widgets\Services\FactoryService;
use Symfony\Component\Cache\Simple\FilesystemCache;
use Symfony\Component\Form\FormBuilderInterface;

/**
 * @abstract    Splash Admin Widgets Factory Service
 */
class WidgetFactoryService implements WidgetProviderInterface
{
    const SERVICE = 'splash.admin.widget.factory';

    const ORIGIN = "<i class='fa fa-github text-success' aria-hidden='true'>&nbsp;</i>&nbsp;";

    const OPTIONS_CACHE_KEY = 'splash.admin.widget.options.';
    const PARAMS_CACHE_KEY = 'splash.admin.widget.parameters.';

    // Fault String
    public $fault_str;

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
     * @abstract    Service Constructor
     */
    public function __construct(FactoryService $WidgetFactory, ConnectorsManager $connectorsManager)
    {
        //====================================================================//
        // Link to WidgetFactory Service
        $this->factory = $WidgetFactory;
        //====================================================================//
        // Link to Connectors Manager Service
        $this->manager = $connectorsManager;
        //====================================================================//
        // Link to Sf Cache
        $this->cache = new FilesystemCache();

        return true;
    }

    /**
     * @abstract   Read Widget Contents
     *
     * @param string $connectorWidgetType Widgets Type Identifier
     * @param array  $parameters          Widget Parameters
     *
     * @return Widget
     */
    public function getWidget(string $connectorWidgetType, $parameters = array())
    {
        //====================================================================//
        // Build Widget Definition
        $this->buildWidgetDefinition($connectorWidgetType, $parameters);
        //====================================================================//
        // Load Widget Contents
        $this->addWidgetContents(
                $connectorWidgetType,
                array_merge_recursive($parameters, $this->getWidgetParameters($connectorWidgetType))
            );
        //====================================================================//
        // Return Splash Widget
        return $this->factory->getWidget();
    }

    /**
     * @abstract   Return Widget Options Array
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
     * @abstract   Update Widget Options Array
     *
     * @param string $connectorWidgetType Widgets Type Identifier
     * @param array  $options             Updated Options
     *
     * @return array
     */
    public function setWidgetOptions(string $connectorWidgetType, array $options): bool
    {
        $this->cache->set(self::OPTIONS_CACHE_KEY.base64_encode($connectorWidgetType), $options);

        return true;
    }

    /**
     * @abstract   Return Widget Parameters Array
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
     * @abstract   Update Widget Parameters Array
     *
     * @param string $connectorWidgetType Widgets Type Identifier
     * @param array  $parameters          Updated Parameters
     *
     * @return array
     */
    public function setWidgetParameters(string $connectorWidgetType, array $parameters): bool
    {
        $this->cache->set(self::PARAMS_CACHE_KEY.base64_encode($connectorWidgetType), $parameters);

        return true;
    }

    /**
     * @abstract   Return Widget Parameters Fields Array
     *
     * @param FormBuilderInterface $builder
     * @param string               $connectorWidgetType    Widgets Type Identifier
     */
    public function populateWidgetForm(FormBuilderInterface $builder, string $connectorWidgetType)
    {
        //==============================================================================
        // Verify Parameter Fields are Available
        $Fields = $this->getWidgetDefinition($connectorWidgetType);
        if (!isset($Fields["parameters"]) || empty($Fields["parameters"])) {
            return null;
        }
        //==============================================================================
        // Add Parameters to Widget Form
        foreach ($Fields["parameters"] as $Field) {
            //====================================================================//
            // Add Single Fields to Form
            $builder->add(
                        $Field->id,
                        FormHelper::formType($Field),
                        FormHelper::formOptions($Field)
                    );
        }
    }

    /**
     * @abstract    Get Widget Connector Service
     *
     * @param string $connectorWidgetType
     *
     * @return AbstractConnector
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
        $connector = $this->manager->get($serverId);
        //==============================================================================
        // Check Widget Exist
        if (!in_array($widgetType, $connector->getAvailableWidgets(), true)) {
            return null;
        }

        return $connector;
    }

    /**
     * @abstract    Read Widgets Definition from Connector
     *
     * @param string $connectorWidgetType
     *
     * @return array
     */
    private function getWidgetDefinition(string $connectorWidgetType)
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
        // Get Target Widget Description
        return $connector->getWidgetDescription($widgetType);
    }
        
    /**
     * @abstract    Widgets Listing
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
        $this->factory
            ->Create()
            ->setService(WidgetFactoryService::SERVICE)
            ->setType($connectorWidgetType)
            ->setName($description['name'])
            ->setDescription($description['description'])
            ->setOrigin(self::ORIGIN.$connector->getSplashType())
            ->setOptions($this->getWidgetOptions($connectorWidgetType))
            ->setExtras(array(
                'WidgetType' => $widgetType,
                'WebserviceId' => $webserviceId,
            ))
        ;

        return $this->factory;
    }

    /**
     * @abstract    Get & Add Contents to Current Widget
     *
     * @param string $connectorWidgetType
     * @param array $parameters
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
        // Import Widget Contents
        if ($widgetContents) {
            $this->factory->setContents($widgetContents);
        } else {
            $this->factory->buildErrorWidget(self::SERVICE, $connectorWidgetType, 'Server Returned Empty Contents.');
        }
        if (isset($widgetContents['blocks'])) {
            $this->factory->addBlocks($widgetContents['blocks']);
        }

        return $this->factory;
    }
}
