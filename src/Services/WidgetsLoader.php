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

use BadPixxel\Widgets\Dictionary\Options;
use BadPixxel\Widgets\Dictionary\Widgets\WidgetWidth;
use BadPixxel\Widgets\Interfaces\Widgets\Loader\WidgetsLoaderInterface;
use BadPixxel\Widgets\Widgets\WidgetConfigurator;
use Splash\Admin\Widgets\RemoteNodeWidget;
use Splash\Bundle\Models\AbstractConnector;
use Splash\Bundle\Services\ConnectorsManager;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;
use Webmozart\Assert\Assert;

/**
 * Configure Widgets for All Available Connexions
 */
#[AutoconfigureTag(WidgetsLoaderInterface::TAG)]
class WidgetsLoader implements WidgetsLoaderInterface
{
    /**
     * List of Static / Tagged Widget Configurators
     *
     * @var WidgetConfigurator[]
     */
    private array $configurators = array();

    public function __construct(
        private readonly RemoteNodeWidget $remoteNodeWidget,
        private readonly ConnectorsManager   $manager,
    ) {
    }

    /**
     * @inheritDoc
     */
    public function getConfigurators(): array
    {
        if (!empty($this->configurators)) {
            return $this->configurators;
        }
        //====================================================================//
        // Walk on Available Connectors
        foreach ($this->manager->getServerConfigurations() as $serverId) {
            //==============================================================================
            // Get Configured Connector
            $connector = $this->manager->get($serverId);
            Assert::isInstanceOf($connector, AbstractConnector::class);
            //==============================================================================
            // Generate Widget Configurators
            foreach ($connector->getAvailableWidgets() as $widgetType) {
                $this->configurators[] = new WidgetConfigurator(
                    loader: 'SplashRemote',
                    service: $this->remoteNodeWidget,
                    channels: array($serverId),
                    options: array(
                        Options::WIDTH => WidgetWidth::M,
                        Options::CACHE_TTL => 60,
                    ),
                    parameters: array(
                        "webserviceId" => $connector->getWebserviceId(),
                        "widgetType" => $widgetType,
                    )
                );
            }
        }

        return $this->configurators;
    }
}
