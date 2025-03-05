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
    name:       'SplashAdmin:Server:Profile',
    template:   '@SplashAdmin/Components/Server/profile.html.twig'
)]
class Profile extends AbstractConnectorTestAware
{
    /**
     * Connector Profile Information
     */
    public array $profile = array();

    #[PostMount]
    public function execute(): void
    {
        //====================================================================//
        // Load Connector Profile
        $this->profile = $this->getConnector()->getProfile();
        //====================================================================//
        // Verify
        if (empty($this->profile)) {
            Splash::log()->war("Connector Profile is Empty");
        }
    }
}
