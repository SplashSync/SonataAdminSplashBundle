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

use Sonata\AdminBundle\Show\ShowMapper;
use Splash\Admin\Fields\FormHelper;
use Splash\Admin\Form\FieldsTransformer;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * @abstract    Splash Objects Fields List Form Type
 */
class FieldsListType extends AbstractType
{
    private $listname;

    /**
     * @param FormBuilderInterface $builder
     * @param array                $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        //==============================================================================
        // Generate Forms Data for Each Field in Collection
        foreach ($options["fields"] as $field) {
            //====================================================================//
            // Detect List Fields
            $list = FormHelper::isListField($field->id);
            $this->listname = $list["listname"];
            //====================================================================//
            // Detect Edit or Show
            $options = ($builder instanceof ShowMapper)
                    ? FormHelper::showOptions($field)
                    : FormHelper::formOptions($field);
            //====================================================================//
            // Generate Field Form Entry
            $builder->add(
                $list["fieldname"],
                FormHelper::formType($field),
                $options
            );

            $builder->get($list["fieldname"])->addModelTransformer(new FieldsTransformer($field->type));
        }
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        parent::configureOptions($resolver);

        $resolver->setDefaults(array(
            'fields' => array(),
        ));
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'Splash_Fields_List_Form';
    }
}
