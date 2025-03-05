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

namespace Splash\Admin\TwigComponent\Objects;

use Splash\Admin\TwigComponent\AbstractConnectorAware;
use Splash\Core\SplashCore as Splash;
use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;
use Symfony\UX\TwigComponent\Attribute\PostMount;

/**
 * Render List of Available Objects on Connector
 */
#[AsTwigComponent(
    name:       'SplashAdmin:Objects:List',
    template:   '@SplashAdmin/Components/Objects/list.html.twig'
)]
class AvailableObjects extends AbstractConnectorAware
{
    /**
     * Connector Available Objects Descriptions
     *
     * @var array[]
     */
    public array $objects = array();

    #[PostMount]
    public function execute(): void
    {
        $connector = $this->getConnector();
        //====================================================================//
        // Load Objects Informations
        $this->objects = array();
        foreach ($connector->getAvailableObjects() as $objectType) {
            $this->objects[$objectType] = $connector->getObjectDescription($objectType);
        }
        //====================================================================//
        // Verify
        if (empty($this->objects)) {
            Splash::log()->war("Connector Objects List is Empty");
        }
    }
}
