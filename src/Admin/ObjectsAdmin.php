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

namespace App\ExplorerBundle\Admin;

use ArrayObject;

use Sonata\AdminBundle\Route\RouteCollection;
use Sonata\AdminBundle\Form\FormMapper;
use Sonata\AdminBundle\Show\ShowMapper;

use Sonata\AdminBundle\Form\Type\CollectionType;

use App\ExplorerBundle\Fields\FormHelper;
use App\ExplorerBundle\Form\FieldsTransformer;
use App\ExplorerBundle\Form\Type\FieldsListType;

/**
 * @abstract    Objects Admin Class for Splash Connectors Explorer
 */
class ObjectsAdmin extends BaseAdmin
{
    
    protected function configureRoutes(RouteCollection $collection)
    {
        $collection->add('switch',  'switch');
        $collection->add('image',   'i/{Path}/{Md5}');
        $collection->add('file',    'f/{Path}/{Md5}');
    }    
    
    /**
     * {@inheritdoc}
     */
    protected function configureShowFields(ShowMapper $showMapper)
    {
        $this->configureFields($showMapper);
    }
            

    /**
     * {@inheritdoc}
     */
    protected function configureFormFields(FormMapper $formMapper)
    {
        $this->configureFields($formMapper);
    }
    
    protected function configureFields($Mapper)
    {
        $Lists =   array();
        //====================================================================//
        // Load Object Fields
        $Fields = $this->getModelManager()->getObjectFields();
        //====================================================================//
        // Walk on Object Fields
        foreach ($Fields as $Field) {
            //====================================================================//
            // Filter ReadOnly Fields
            if (empty($Field->write)) {
                continue;
            }
            //====================================================================//
            // Add Single Fields to Mapper
            if ( !FormHelper::isListField($Field->type)) {
                $this->buildFieldForm($Mapper, $Field);
                continue;
            }
            //====================================================================//
            // Add List Field to Buffer
            $List   =   FormHelper::isListField($Field->id);
            $Lists  =   array_merge_recursive($Lists, array(
                $List["listname"] => array(
                    $List["fieldname"]  =>     $Field
                )));
        }
       
        //====================================================================//
        // Walk on Object Lists
        foreach ($Lists as $Name => $Fields) {
            $this->buildFieldListForm($Mapper, $Name, $Fields);
        }
        
    }    
    
    public function buildFieldForm($mapper, ArrayObject $Field)
    {
        if ($mapper instanceof ShowMapper) {
            $options    =   FormHelper::showOptions($Field);
        } else {
            $options    =   FormHelper::formOptions($Field);
        }
        $mapper
            ->with(FormHelper::formGroup($Field), array('class' => 'col-md-6'))
                ->add(
                        $Field->id, 
                        FormHelper::formType($Field),
                        $options
                    )
            ->end()
        ;
        if ($mapper instanceof FormMapper) {
            $mapper->get($Field->id)->addModelTransformer(new FieldsTransformer($Field->type));
        }
        return $this;
    }      

    public function buildFieldListForm($mapper, string $Name, array $Fields)
    {
        
        $options    =   array(
                    'entry_type'    => FieldsListType::class,
                    'entry_options' => array(
                        'label'     => false,
                        'fields'    => $Fields,
                        ),
                    'required' => false,
                    'allow_add' => true,
                    'allow_delete' => true,
                );
                
        if ($mapper instanceof ShowMapper) {
            $options    =   array_merge_recursive($options, FormHelper::showOptions($Fields, true));
        }        
        
        $mapper
            ->with($Name, array('class' => 'col-md-6'))
                ->add($Name, CollectionType::class, $options, array())
            ->end()
        ;
        
//        if ($mapper instanceof FormMapper) {
//            $mapper->get($Name)->addModelTransformer(new FieldsTransformer(SPL_T_LIST));
////dump($mapper->get($Name));            
//        }              
        return $this;
    }      

    
    /**
     * {@inheritdoc}
     */
    public function getNewInstance()
    {
        $Object =   new ArrayObject(array("id" => null), ArrayObject::ARRAY_AS_PROPS);
        
        
        $Fields = $this->getModelManager()->getObjectFields();
        foreach ($Fields as $Field) {
            
            //====================================================================//
            // Add Empty List Field to Object
            $List   =   FormHelper::isListField($Field->id);
            if ( $List ) {
                $Object[$List["listname"]] = null;
            //====================================================================//
            // Add Empty Single Field to Object
            } else {
                $Object[$Field->id] = null;
            }
        
        }
        return $Object;
    }    
}
