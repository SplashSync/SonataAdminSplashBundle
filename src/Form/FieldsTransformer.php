<?php

/**
 * This file is part of SplashSync Project.
 *
 * Copyright (C) Splash Sync <www.splashsync.com>
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 * 
 * @author Bernard Paquier <contact@splashsync.com>
 */

namespace App\ExplorerBundle\Form;

use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Form\Exception\TransformationFailedException;
use Symfony\Component\HttpFoundation\File\UploadedFile;

use Splash\Models\Fields\FieldsManagerTrait;
use Splash\Models\Objects\ImagesTrait;

use Splash\Core\SplashCore as Splash;

/**
 * Description of FieldsTransformer
 *
 * @author nanard33
 */
class FieldsTransformer implements DataTransformerInterface {

    use FieldsManagerTrait;
    use ImagesTrait;
    
    private $type;
    
    public function __construct(string $Type)
    {
        $this->type = $Type;
    }    
    
    /**
     * Transforms a Splash Field Data into Php Type
     */
    public function transform($data)
    {
        //====================================================================//
        // Get Form Type        
        switch(self::baseType($this->type)) {
            case SPL_T_BOOL:
                return (bool) $data;
                
            case SPL_T_INT:
                return (int) $data;
                
            case SPL_T_DOUBLE:
                return (double) $data;
                
            case SPL_T_IMG:
                if (empty($data)) {
                    return array();
                }
                return $data;
                
        } 
        return $data;
    }

    /**
     * Transforms a Form Data to Splash Field Data
     * @throws TransformationFailedException
     */
    public function reverseTransform($data)
    {
        //====================================================================//
        // Get Form Type        
        switch(self::baseType($this->type)) {
            
            case SPL_T_IMG:
                //====================================================================//
                // Check Uploaded File        
                if (!isset($data["upload"]) || !($data["upload"] instanceof UploadedFile) || (!$data["upload"]->isValid())) {
                    return $data;
                } 
                //====================================================================//
                // Convert Symfony File to Splash Image Array        
                $Image  = self::images()->encode(
                        $data["upload"]->getClientOriginalName(),
                        $data["upload"]->getFilename(),
                        $data["upload"]->getPath() . "/"
                    );
                //====================================================================//
                // Safety Check
                if (!$Image) {
                    return $data;
                } 
                //====================================================================//
                // Copy Path to File (For Writing) 
                $Image["file"]      =   $Image["path"];
                $Image["filename"]  =   $data["upload"]->getClientOriginalName();
                return $Image;
        } 
        
        return $data;
    }
    
}
