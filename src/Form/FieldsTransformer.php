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

namespace Splash\Admin\Form;

use ArrayObject;
use Splash\Core\SplashCore as Splash;
use Splash\Models\Fields\FieldsManagerTrait;
use Splash\Models\Helpers\InlineHelper;
use Splash\Models\Objects\FilesTrait;
use Splash\Models\Objects\ImagesTrait;
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

    /**
     * @var string
     */
    private $type;

    /**
     * @var null|array
     */
    private $choices;

    /**
     * Class Constructor
     *
     * @param string $objectType
     */
    public function __construct(string $objectType, ?array $choices)
    {
        $this->type = $objectType;
        $this->choices = $choices;
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
            case SPL_T_STREAM:
                if (empty($data)) {
                    return array();
                }

                return $data;
            case SPL_T_INLINE:
                return empty($this->choices) ? $data : InlineHelper::toArray($data);
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
        $fieldType = (string) self::baseType($this->type);
        //====================================================================//
        // Get Form Type
        switch ($fieldType) {
            case SPL_T_IMG:
            case SPL_T_FILE:
            case SPL_T_STREAM:
                return $this->reverseFileTransform(
                    $fieldType,
                    ($data instanceof ArrayObject) ? $data->getArrayCopy(): $data
                );
            case SPL_T_INLINE:
                return empty($this->choices) ? $data : InlineHelper::fromArray($data);
        }

        return $data;
    }

    /**
     * @param string $type
     * @param array  $data
     *
     * @return null|array
     */
    private function reverseFileTransform(string $type, array $data)
    {
        //====================================================================//
        // Check Uploaded File
        if (!isset($data['upload']) && array_key_exists("upload", $data) && (1 == count($data))) {
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
        $file = (SPL_T_IMG == $type)
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
}
