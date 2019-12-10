<?php

/*
 *  This file is part of SplashSync Project.
 *
 *  Copyright (C) 2015-2019 Splash Sync  <www.splashsync.com>
 *
 *  This program is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 *
 *  For the full copyright and license information, please view the LICENSE
 *  file that was distributed with this source code.
 */

namespace Splash\Admin\Form;

use Splash\Core\SplashCore as Splash;
use Splash\Models\Fields\FieldsManagerTrait;
use Splash\Models\Objects\ImagesTrait;
use Splash\Models\Objects\FilesTrait;
use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;

/**
 * Splash Fields Data Transformer.
 */
class FieldsTransformer implements DataTransformerInterface
{
    use FieldsManagerTrait;
    use ImagesTrait;
    use FilesTrait;

    private $type;

    /**
     * Class Constructor
     *
     * @param string $objectType
     */
    public function __construct(string $objectType)
    {
        $this->type = $objectType;
    }

    /**
     * @param mixed $data
     *
     * @return mixed
     */
    public function transform($data)
    {
        //====================================================================//
        // Get Form Type
        switch (self::baseType($this->type)) {
            case SPL_T_BOOL:
                return (bool) $data;
            case SPL_T_INT:
                return (int) $data;
            case SPL_T_DOUBLE:
                return (float) $data;
            case SPL_T_FILE:
            case SPL_T_IMG:
                if (empty($data)) {
                    return array();
                }

                return $data;
        }

        return $data;
    }

    /**
     * @param mixed $data
     *
     * @return mixed
     */
    public function reverseTransform($data)
    {
        //====================================================================//
        // Get Form Type
        switch (self::baseType($this->type)) {
            case SPL_T_IMG:
            case SPL_T_FILE:
                //====================================================================//
                // Check Uploaded File
                if (!isset($data['upload']) && array_key_exists("upload", $data) && (count($data) == 1)) {
                    return null;
                }
                if (!isset($data['upload']) || !($data['upload'] instanceof UploadedFile) || (!$data['upload']->isValid())) {
                    return $data;
                }
                //====================================================================//
                // Prepare data For Encoding
                $originalName = (string) $data['upload']->getClientOriginalName();
                $fileName = $data['upload']->getFilename();
                $filePath = $data['upload']->getPath().'/';
                //====================================================================//
                // Convert Symfony File to Splash File|Image Array
                $file = (self::baseType($this->type) == SPL_T_IMG) 
                    ? self::images()->encode($originalName, $fileName, $filePath)
                    : self::files()->encode($originalName, $fileName, $filePath);
                //====================================================================//
                // Safety Check
                if (!$file) {
                    return $data;
                }
                //====================================================================//
                // Copy Path to File (For Writing)
                $file['file'] = $file['path'];
                $file['filename'] = $data['upload']->getClientOriginalName();

                return $file;
        }

        return $data;
    }
}
