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

use Splash\Admin\TwigComponent\AbstractConnectorTestAware;
use Splash\Core\SplashCore as Splash;
use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;
use Symfony\UX\TwigComponent\Attribute\PostMount;

/**
 * Execute Server Ping
 */
#[AsTwigComponent(
    name:       'SplashAdmin:Server:Ping',
    template:   '@SplashAdmin/Components/Server/tests_results.html.twig'
)]
class PingTest extends AbstractConnectorTestAware
{
    #[PostMount]
    public function execute(): void
    {
        $this->title = "Connector Ping";
        //====================================================================//
        // Execute Server Ping
        if ($this->result = $this->getConnector()->ping()) {
            Splash::log()->msg("Ping test passed");
        }
        //====================================================================//
        // Store Server logs
        $this->logs = Splash::log()->getHtmlLog(true);
    }
}
