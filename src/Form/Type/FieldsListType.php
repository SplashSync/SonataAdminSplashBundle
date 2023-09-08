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

namespace Splash\Admin\Form\Type;

use Splash\Admin\Fields\FormHelper;
use Splash\Admin\Form\FieldsTransformer;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Splash Objects Fields List Form Type
 */
class FieldsListType extends AbstractType
{
    /**
     * @param FormBuilderInterface $builder
     * @param array                $options
     *
     * @return void
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        //==============================================================================
        // Generate Forms Data for Each Field in Collection
        foreach ($options["fields"] as $field) {
            //====================================================================//
            // Detect List Fields
            $list = FormHelper::isListField($field["id"]);
            if (!is_array($list)) {
                continue;
            }
            //====================================================================//
            // Generate Field Form Entry
            $builder->add(
                $list["fieldname"],
                FormHelper::formType($field),
                FormHelper::formOptions($field)
            );

            $builder->get($list["fieldname"])->addModelTransformer(
                new FieldsTransformer($field["type"], !empty($field["choices"]) ? $field["choices"] : null)
            );
        }
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        parent::configureOptions($resolver);

        $resolver->setDefaults(array(
            'fields' => array(),
        ));
    }

    /**
     * {@inheritdoc}
     */
    public function getName(): string
    {
        return 'Splash_Fields_List_Form';
    }
}
