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

use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

use Burgov\Bundle\KeyValueFormBundle\Form\Type\KeyValueType;
use Burgov\Bundle\KeyValueFormBundle\Form\DataTransformer\HashToKeyValueArrayTransformer;

use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\LocaleType;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;

/**
 * @abstract    Splash Multilang Fields Form Type
 */
class MultilangType extends KeyValueType {
    
    /**
     * {@inheritdoc}
     */    
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->addModelTransformer(new HashToKeyValueArrayTransformer($options['use_container_object']));

        $builder->addEventListener(FormEvents::PRE_SET_DATA, function(FormEvent $e) {
            $input = $e->getData();

            if ("" === $input) {
                $e->setData(array());
                return;
            }
            
            if (null === $input) {
                return;
            }
            
            $output = array();

            foreach ($input as $key => $value) {
                $output[] = array(
                    'key' => $key,
                    'value' => $value
                );
            }

            $e->setData($output);
        }, 1);
    }
    
    /**
     * {@inheritdoc}
     */    
    public function configureOptions(OptionsResolver $resolver)
    {
        parent::configureOptions($resolver);
        
        $resolver->setDefaults(array(
            'value_type'        =>  TextareaType::class,
            'key_type'          =>  LocaleType::class,
            //====================================================================//
            // Collection Form Parameters  
            'allow_add'         => true,
            'allow_delete'      => true,
        ));
               
    }    
    
    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'Splash_Multilang_Form';
    }
}
