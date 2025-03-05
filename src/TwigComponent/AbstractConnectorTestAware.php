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

namespace Splash\Admin\TwigComponent;

/**
 * Abstract base class for components that require a connector.
 */
abstract class AbstractConnectorTestAware extends AbstractConnectorAware
{
    /**
     * Title
     */
    public string $title = "Tests Results Block";

    /**
     * Tests Results
     */
    public bool $result = false;

    /**
     * Server Logs
     */
    public string $logs = "";
}
