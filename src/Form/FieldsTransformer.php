<?php

/*
 *  This file is part of SplashSync Project.
 *
 *  Copyright (C) 2015-2018 Splash Sync  <www.splashsync.com>
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
use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;

/**
 * @abstract    Splash Fields Data Transformer.
 */
class FieldsTransformer implements DataTransformerInterface
{
    use FieldsManagerTrait;
    use ImagesTrait;

    private $type;

    /**
     * @abstract    Class Constructor
     *
     * @param string $objectType
     */
    public function __construct(string $objectType)
    {
        $this->type = $objectType;
    }

    /**
     * {@inheritdoc}
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
            case SPL_T_IMG:
                if (empty($data)) {
                    return array();
                }

                return $data;
        }

        return $data;
    }

    /**
     * {@inheritdoc}
     */
    public function reverseTransform($data)
    {
        //====================================================================//
        // Get Form Type
        switch (self::baseType($this->type)) {
            case SPL_T_IMG:
                //====================================================================//
                // Check Uploaded File
                if (!isset($data['upload']) || !($data['upload'] instanceof UploadedFile) || (!$data['upload']->isValid())) {
                    return $data;
                }
                //====================================================================//
                // Convert Symfony File to Splash Image Array
                $image = self::images()->encode(
                    (string) $data['upload']->getClientOriginalName(),
                    $data['upload']->getFilename(),
                    $data['upload']->getPath().'/'
                );
                //====================================================================//
                // Safety Check
                if (!$image) {
                    return $data;
                }
                //====================================================================//
                // Copy Path to File (For Writing)
                $image['file'] = $image['path'];
                $image['filename'] = $data['upload']->getClientOriginalName();

                return $image;
        }

        return $data;
    }
}
