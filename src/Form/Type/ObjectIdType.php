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

namespace Splash\Admin\Form\Type;

use Splash\Models\Fields\FieldsManagerTrait;
use Splash\Models\Helpers\ObjectsHelper;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\CallbackTransformer;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * @abstract    Splash Objects Fields Object Id Form Type
 */
class ObjectIdType extends AbstractType
{
    use FieldsManagerTrait;
        
    /**
     * @abstract Build Object Id Form
     *
     * @param FormBuilderInterface $formBuilder
     * @param array                $options
     */
    public function buildForm(FormBuilderInterface $formBuilder, array $options)
    {
        //==============================================================================
        //  Object ID Widget
        $formBuilder->add("ObjectId", TextType::class, array(
            'required'          => true,
        ));
        //==============================================================================
        //  Object Type Widget
        $formBuilder->add("ObjectType", HiddenType::class, array(
            'required'          => true,
            'empty_data'        => $options['object_type'],
            'data'              => $options['object_type'],
        ));
        //==============================================================================
        //  Add Data Transformers to Form
        $formBuilder
            ->addModelTransformer(new CallbackTransformer(
                function ($data) {
                    if (!self::isIdField($data)) {
                        return array();
                    }

                    return self::isIdField($data);
                },
                function ($data) {
                    if (empty($data["ObjectId"])) {
                        return null;
                    }

                    return ObjectsHelper::encode($data["ObjectType"], $data["ObjectId"]);
                }
            ));
    }
    
    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        parent::configureOptions($resolver);
        $resolver->setDefaults(array(
            'object_type'       =>  null,
        ));
    }
    
    /**
     * @return string
     */
    public function getName()
    {
        return 'splash_object_id';
    }
}
