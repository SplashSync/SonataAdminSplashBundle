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

namespace Splash\Admin\TwigComponent\Server;

use ArrayObject;
use Splash\Admin\TwigComponent\AbstractConnectorTestAware;
use Splash\Core\SplashCore as Splash;
use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;
use Symfony\UX\TwigComponent\Attribute\PostMount;

/**
 * Display Server Configuration
 */
#[AsTwigComponent(
    name:       'SplashAdmin:Server:Config',
    template:   '@SplashAdmin/Components/Server/configuration.html.twig'
)]
class Configuration extends AbstractConnectorTestAware
{
    /**
     * Connector Configuration
     */
    public ArrayObject $config;

    #[PostMount]
    public function execute(): void
    {
        //====================================================================//
        // Load Connector Profile
        $this->config = Splash::configuration();
        //====================================================================//
        // Verify
        if (empty($this->config->count())) {
            Splash::log()->war("Connector Configuration is Empty");
        }
    }
}
