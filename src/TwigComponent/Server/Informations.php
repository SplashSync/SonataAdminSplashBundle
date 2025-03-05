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
use Webmozart\Assert\Assert;

/**
 * Execute Server Self Tests
 */
#[AsTwigComponent(
    name:       'SplashAdmin:Server:Infos',
    template:   '@SplashAdmin/Components/Server/informations.html.twig'
)]
class Informations extends AbstractConnectorTestAware
{
    /**
     * Connector Contact Information
     */
    public ArrayObject $infos;

    #[PostMount]
    public function execute(): void
    {
        //====================================================================//
        // Load Connector Profile
        $this->infos = $this
            ->getConnector()
            ->informations(new ArrayObject(array(), ArrayObject::ARRAY_AS_PROPS))
        ;
        //====================================================================//
        // Verify
        if (empty($this->infos->count())) {
            Splash::log()->war("Connector Information is Empty");
        }
    }

    /**
     * Retrieves the URL source or raw base64-encoded logo data if available.
     *
     * Ensures that the logo URL and raw logo data, if provided, are non-empty strings.
     * The method returns the URL if it is defined; otherwise, it generates a base64
     * image string from the raw logo data, provided it is available.
     *
     * @return null|string The logo source URL or encoded logo data; null if neither exists.
     */
    public function getLogoSrc(): ?string
    {
        Assert::nullOrString(
            $logoUrl = $this->infos->logourl ?? null,
            "Connector Logo Url must be a non empty string"
        );
        Assert::nullOrString(
            $logoRaw = $this->infos->logoraw ?? null,
            "Connector Logo Raw must be a non empty string"
        );
        //====================================================================//
        // Logo url is Defined
        if (!empty($logoUrl)) {
            return $logoUrl;
        }
        //====================================================================//
        // Raw Logo is Defined
        if (!empty($logoRaw)) {
            return sprintf("data:image/gif;base64,%s", $logoRaw);
        }

        return null;
    }
}
