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

namespace App\ExplorerBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\CallbackTransformer;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\PercentType;
use Symfony\Component\Form\Extension\Core\Type\CurrencyType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;

use Splash\Models\Helpers\PricesHelper;

/**
 * @abstract    Splash Objects Fields Price Form Type
 */
class PriceType extends AbstractType
{
    public function buildForm(FormBuilderInterface $FormBuilder, array $options)
    {
        //==============================================================================
        //  Create Without Tax Price Widget
        $FormBuilder->add("ht", NumberType::class,  array(
                'required'          => False,
                'label'             => "Tax Excl.",
                'empty_data'        => '0',
        )); 
        //==============================================================================
        //  Create With Tax Price Widget
        $FormBuilder->add("ttc", HiddenType::class,  array(
                'required'          => False,
                'label'             => "Tax incl.",
                'empty_data'        => '0',
        )); 
        //==============================================================================
        //  Create VAT Percent Widget
        $FormBuilder->add("vat", PercentType::class,  array(
                'required'          => False,
                'label'             => "VAT",
                'type'              => 'integer',
                'empty_data'        => '0',
        )); 
        //==============================================================================
        //  Create Currency Code Widget
        $FormBuilder->add("code", CurrencyType::class,  array(
                'required'          => True,
                'label'             => "Currency",
                'empty_data'        => 'EUR',
        )); 

        //==============================================================================
        //  Add Data Transformers to Form
        $FormBuilder
            ->addModelTransformer(new CallbackTransformer(
                function ($data) {
                    if (is_null($data) || is_scalar($data)) {
                        return PricesHelper::encode( (double) 0, (double) 20, Null, "EUR" ,"","");
                    }
                    return $data;
                },
                function ($data) {
                    return PricesHelper::encode( (double) $data["ht"], (double) $data["vat"],Null,$data["code"],"","");
                }
            ));               
    }
    
    /**
     * @return string
     */
    public function getName()
    {
        return 'splash_price';
    }
    
}