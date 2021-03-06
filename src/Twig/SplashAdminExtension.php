<?php

/*
 *  This file is part of SplashSync Project.
 *
 *  Copyright (C) 2015-2021 Splash Sync  <www.splashsync.com>
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
     * Get Extention Available Filters
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
     * @return array|false
     */
    public function isIdField($input)
    {
        return FieldsManager::isIdField($input);
    }

    /**
     * Decode a string to extract Object Identifier Data Type
     *
     * @param string $unput Id Field String
     *
     * @return bool|string
     */
    public function objectType($unput)
    {
        return FieldsManager::objectType($unput);
    }

    /**
     * Decode a string to extract Object Identifier Data Type
     *
     * @param string $input Id Field String
     *
     * @return bool|string
     */
    public function objectId($input)
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
    public function isListField($input)
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
     * @return bool|string
     */
    public function getListFieldData($input)
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
            //====================================================================//
            // If List Detected, Return Field Identifier
            return $list[0];
        }

        return false;
    }

    /**
     * Decode a string to extract List Name String
     *
     * @param string $input List Field String
     *
     * @return bool|string
     */
    public function getListFieldName($input)
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
            //====================================================================//
            // If List Detected, Return List Name
            return $list[1];
        }

        return false;
    }

    /**
     * Access PHP Core Function
     *
     * @param string $input
     *
     * @return bool|string
     */
    public function base64Encode($input)
    {
        return base64_encode($input);
    }

    /**
     * Access PHP Core Function
     *
     * @param string $input
     *
     * @return bool|string
     */
    public function base64Decode($input)
    {
        return base64_decode($input, true);
    }

    /**
     * Read Raw Logs from Splash Module as Html List
     *
     * @return string
     */
    public function getHtmlLogs()
    {
        return Splash::log()->GetHtmlLog(true);
    }

    /**
     * Get File Path Extension Name
     *
     * @param string $input
     *
     * @return bool|string
     */
    public function filetypeFilter($input)
    {
        return pathinfo($input, PATHINFO_EXTENSION);
    }

    /**
     * @return string
     */
    public function getName()
    {
        return 'App_Explorer_Twig_Extension';
    }
}
