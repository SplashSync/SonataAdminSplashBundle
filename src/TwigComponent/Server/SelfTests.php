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
 * Execute Server Self Tests
 */
#[AsTwigComponent(
    name:       'SplashAdmin:Server:SelfTests',
    template:   '@SplashAdmin/Components/Server/tests_results.html.twig'
)]
class SelfTests extends AbstractConnectorTestAware
{
    #[PostMount]
    public function execute(): void
    {
        $this->title = "Connector Self-Tests Results";
        //====================================================================//
        // Execute Splash Self-Test
        if ($this->result = $this->getConnector()->selfTest()) {
            Splash::log()->msg("Self-Test Passed");
        }
        //====================================================================//
        // Store Server logs
        $this->logs = Splash::log()->getHtmlLog(true);
    }
}
