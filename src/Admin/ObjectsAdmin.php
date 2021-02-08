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

namespace Splash\Admin\Admin;

use ArrayObject;
use Sonata\AdminBundle\Form\FormMapper;
use Sonata\AdminBundle\Form\Type\CollectionType;
use Sonata\AdminBundle\Mapper\BaseGroupedMapper;
use Sonata\AdminBundle\Route\RouteCollection;
use Sonata\AdminBundle\Show\ShowMapper;
use Splash\Admin\Fields\FormHelper;
use Splash\Admin\Form\FieldsTransformer;
use Splash\Admin\Form\Type\FieldsListType;
use Splash\Admin\Model\ObjectsManager;

/**
 * Objects Admin Class for Splash Connectors Explorer
 */
class ObjectsAdmin extends AbstractAdmin
{
    /**
     * Build Splash Objects Single Field Form.
     *
     * @param BaseGroupedMapper $mapper
     * @param ArrayObject       $field
     *
     * @return $this
     */
    public function buildFieldForm($mapper, ArrayObject $field)
    {
        // This Should never happen, but required for PhpStan
        if (!($mapper instanceof FormMapper) && !($mapper instanceof ShowMapper)) {
            return $this;
        }

        $options = ($mapper instanceof ShowMapper)
                ? FormHelper::showOptions($field)
                : FormHelper::formOptions($field);

        $mapper->with(FormHelper::formGroup($field), array('class' => 'col-md-6'));
        $mapper->add(
            $field->id,
            FormHelper::formType($field),
            $options
        );
        $mapper->end();

        if ($mapper instanceof FormMapper) {
            $mapper->get($field->id)->addModelTransformer(new FieldsTransformer($field->type));
        }

        return $this;
    }

    /**
     * Builde Splash Object List Field Form.
     *
     * @param FormMapper|ShowMapper $mapper
     * @param string                $name
     * @param array                 $objectFields
     *
     * @return $this
     */
    public function buildFieldListForm($mapper, string $name, array $objectFields)
    {
        $options = array(
            'entry_type' => FieldsListType::class,
            'entry_options' => array(
                'label' => false,
                'fields' => $objectFields,
            ),
            'required' => false,
            'allow_add' => true,
            'allow_delete' => true,
        );

        if ($mapper instanceof ShowMapper) {
            $options = array_merge_recursive($options, FormHelper::showOptions($objectFields, true));
        }

        $mapper
            ->with($name, array('class' => 'col-md-6'))
            ->add($name, CollectionType::class, $options)
            ->end()
        ;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getNewInstance()
    {
        $newObject = new ArrayObject(array('id' => null), ArrayObject::ARRAY_AS_PROPS);

        //====================================================================//
        // Load Object Fields
        /** @var ObjectsManager $modelManager */
        $modelManager = $this->getModelManager();
        $objectFields = $modelManager->getObjectFields();
        //====================================================================//
        // Walk on Object Fields
        foreach ($objectFields as $field) {
            //====================================================================//
            // Add Empty List Field to Object
            $list = FormHelper::isListField($field->id);
            if ($list) {
                $newObject[$list['listname']] = null;

                continue;
            }
            //====================================================================//
            // Add Empty Single Field to Object
            $newObject[$field->id] = null;
        }

        return $newObject;
    }

    /**
     * @param RouteCollection $collection
     */
    protected function configureRoutes(RouteCollection $collection): void
    {
        $collection->add('switch', 'switch');
        $collection->add('image', 'i/{path}/{md5}');
        $collection->add('file', 'f/{path}/{md5}');
    }

    /**
     * {@inheritdoc}
     */
    protected function configureShowFields(ShowMapper $showMapper): void
    {
        //====================================================================//
        // Override Sonata Base View Template
        $this
            ->getTemplateRegistry()
            ->setTemplate("show", "@SplashAdmin/CRUD/show_object.html.twig");

        $this->configureFields($showMapper);
    }

    /**
     * {@inheritdoc}
     */
    protected function configureFormFields(FormMapper $formMapper): void
    {
        //====================================================================//
        // Override Sonata Base View Template
        $this
            ->getTemplateRegistry()
            ->setTemplate("edit", "@SplashAdmin/CRUD/edit_object.html.twig");

        $this->configureFields($formMapper);
    }

    /**
     * @param FormMapper|ShowMapper $mapper
     *
     * @return void
     */
    protected function configureFields($mapper)
    {
        $lists = array();
        //====================================================================//
        // Load Object Fields
        /** @var ObjectsManager $modelManager */
        $modelManager = $this->getModelManager();
        $objectFields = $modelManager->getObjectFields();
        //====================================================================//
        // Walk on Object Fields
        /** @var ArrayObject $field */
        foreach ($objectFields as $field) {
            //====================================================================//
            // Filter ReadOnly Fields
            if (!($mapper instanceof ShowMapper) && empty($field->write)) {
                continue;
            }
            //====================================================================//
            // Add Single Fields to Mapper
            if (!FormHelper::isListField($field->type)) {
                $this->buildFieldForm($mapper, $field);

                continue;
            }
            //====================================================================//
            // Add List Field to Buffer
            $list = FormHelper::isListField($field->id);
            if (!is_array($list)) {
                continue;
            }
            $lists = array_merge_recursive(
                $lists,
                array(
                    $list['listname'] => array($list['fieldname'] => $field),
                )
            );
        }

        //====================================================================//
        // Walk on Object Lists
        foreach ($lists as $name => $objectFields) {
            $this->buildFieldListForm($mapper, $name, $objectFields);
        }
    }
}
