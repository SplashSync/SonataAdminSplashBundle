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

use Splash\Core\SplashCore as Splash;
use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;

/**
 * Display Server Logs from Splash Logger
 */
#[AsTwigComponent(
    name:       'SplashAdmin:Server:Logs',
    template:   '@SplashAdmin/Components/Server/logs.html.twig'
)]
class ServerLogs
{
    /**
     * Check if Server Logs is Empty
     */
    public function hasLogs(): bool
    {
        return !empty(array_filter(Splash::log()->getRawLog(false)));
    }

    /**
     * Get Server Logs from Splash Framework
     */
    public function getLogs(): string
    {
        return Splash::log()->getHtmlLog(true);
    }
}
