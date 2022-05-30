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

namespace Splash\Admin\Twig;

use Splash\Components\FieldsManager;
use Splash\Core\SplashCore as Splash;
use Splash\Models\Helpers\InlineHelper;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

/**
 * Splash Admin Twig Admin Extension
 */
class SplashAdminExtension extends AbstractExtension
{
    /**
     * Get Extension Available Filters
     *
     * @return array
     */
    public function getFilters()
    {
        return array(
            new TwigFilter('isIdField', array($this, 'isIdField')),
            new TwigFilter('objectType', array($this, 'objectType')),
            new TwigFilter('objectId', array($this, 'objectId')),
            new TwigFilter('isListField', array($this, 'isListField')),
            new TwigFilter('getListFieldName', array($this, 'getListFieldName')),
            new TwigFilter('getListFieldData', array($this, 'getListFieldData')),
            new TwigFilter('filetype', array($this, "filetypeFilter")),
            new TwigFilter('base64_encode', array($this, "base64Encode")),
            new TwigFilter('base64_decode', array($this, "base64Decode")),
            new TwigFilter('htmlLogs', array($this, "getHtmlLogs")),
            new TwigFilter('inline_decode', array(InlineHelper::class, "toArray")),
        );
    }

    /**
     * Identify if a string is an Object Identifier Data
     *
     * @param string $input Id Field String
     *
     * @return null|array
     */
    public function isIdField(string $input): ?array
    {
        return FieldsManager::isIdField($input);
    }

    /**
     * Decode a string to extract Object Identifier Data Type
     *
     * @param string $input Id Field String
     *
     * @return null|string
     */
    public function objectType(string $input): ?string
    {
        return FieldsManager::objectType($input);
    }

    /**
     * Decode a string to extract Object Identifier Data Type
     *
     * @param string $input Id Field String
     *
     * @return null|string
     */
    public function objectId(string $input): ?string
    {
        return FieldsManager::objectId($input);
    }

    /**
     * Identify if a string is a List Field String
     *
     * @param string $input List Field String
     *
     * @return bool
     */
    public function isListField(string $input): bool
    {
        //====================================================================//
        // Safety Check
        if (empty($input)) {
            return false;
        }
        //====================================================================//
        // Detects Lists
        $list = explode(LISTSPLIT, $input);
        if (is_array($list) && (2 == count($list))) {
            return true;
        }

        return false;
    }

    /**
     * Decode a list string to extract Field Identifier
     *
     * @param string $input List Field String
     *
     * @return null|string
     */
    public function getListFieldData(string $input): ?string
    {
        //====================================================================//
        // Safety Check
        if (empty($input)) {
            return null;
        }
        //====================================================================//
        // Detects Lists
        $list = explode(LISTSPLIT, $input);
        if (is_array($list) && (2 == count($list))) {
            //====================================================================//
            // If List Detected, Return Field Identifier
            return $list[0];
        }

        return null;
    }

    /**
     * Decode a string to extract List Name String
     *
     * @param string $input List Field String
     *
     * @return null|string
     */
    public function getListFieldName(string $input): ?string
    {
        //====================================================================//
        // Safety Check
        if (empty($input)) {
            return null;
        }
        //====================================================================//
        // Detects Lists
        $list = explode(LISTSPLIT, $input);
        if (is_array($list) && (2 == count($list))) {
            //====================================================================//
            // If List Detected, Return List Name
            return $list[1];
        }

        return null;
    }

    /**
     * Access PHP Core Function
     *
     * @param string $input
     *
     * @return string
     */
    public function base64Encode(string $input): string
    {
        return base64_encode($input);
    }

    /**
     * Access PHP Core Function
     *
     * @param string $input
     *
     * @return null|string
     */
    public function base64Decode(string $input): ?string
    {
        return base64_decode($input, true) ?: null;
    }

    /**
     * Read Raw Logs from Splash Module as Html List
     *
     * @return string
     */
    public function getHtmlLogs(): string
    {
        return Splash::log()->getHtmlLog(true);
    }

    /**
     * Get File Path Extension Name
     *
     * @param string $input
     *
     * @return null|string
     */
    public function filetypeFilter(string $input): ?string
    {
        return pathinfo($input, PATHINFO_EXTENSION) ?: null;
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return 'App_Explorer_Twig_Extension';
    }
}
