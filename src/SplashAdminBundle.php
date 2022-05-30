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

namespace Splash\Admin;

use Symfony\Component\HttpKernel\Bundle\Bundle;

/**
 * @abstract    Splash Sonata Admin bundle for Symfony
 */
class SplashAdminBundle extends Bundle
{
    /**
     * {@inheritdoc}
     */
    public function boot(): void
    {
        if (!defined('SPLASH_DEBUG')) {
            define('SPLASH_DEBUG', '1');
        }
    }
}
