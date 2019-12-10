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

namespace Splash\Admin\Form\Type;

use Splash\Models\Helpers\PricesHelper;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\CallbackTransformer;
use Symfony\Component\Form\Extension\Core\Type\CurrencyType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\PercentType;
use Symfony\Component\Form\FormBuilderInterface;

/**
 * Splash Objects Fields Price Form Type
 */
class PriceType extends AbstractType
{
    /**
     * Build Price Form
     *
     * @param FormBuilderInterface $formBuilder
     * @param array                $options
     *
     * @return void
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function buildForm(FormBuilderInterface $formBuilder, array $options): void
    {
        //==============================================================================
        //  Create Without Tax Price Widget
        $formBuilder->add("ht", NumberType::class, array(
            'required' => false,
            'label' => "Tax Excl.",
            'empty_data' => '0',
        ));
        //==============================================================================
        //  Create With Tax Price Widget
        $formBuilder->add("ttc", HiddenType::class, array(
            'required' => false,
            'label' => "Tax incl.",
            'empty_data' => '0',
        ));
        //==============================================================================
        //  Create VAT Percent Widget
        $formBuilder->add("vat", PercentType::class, array(
            'required' => false,
            'label' => "VAT",
            'type' => 'integer',
            'empty_data' => '0',
        ));
        //==============================================================================
        //  Create Currency Code Widget
        $formBuilder->add("code", CurrencyType::class, array(
            'required' => true,
            'label' => "Currency",
            'empty_data' => 'EUR',
        ));

        //==============================================================================
        //  Add Data Transformers to Form
        $formBuilder
            ->addModelTransformer(new CallbackTransformer(
                function ($data) {
                    if (is_null($data) || is_scalar($data)) {
                        return PricesHelper::encode((float) 0, (float) 20, null, "EUR", "", "");
                    }

                    return $data;
                },
                function ($data) {
                    return PricesHelper::encode((float) $data["ht"], (float) $data["vat"], null, $data["code"], "", "");
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
