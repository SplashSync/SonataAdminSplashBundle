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

namespace Splash\Admin\Admin;

use ArrayObject;
use Exception;
use Sonata\AdminBundle\Datagrid\DatagridMapper;
use Sonata\AdminBundle\Datagrid\ListMapper;
use Sonata\AdminBundle\Form\FormMapper;
use Sonata\AdminBundle\Form\Type\CollectionType;
use Sonata\AdminBundle\Mapper\BaseGroupedMapper;
use Sonata\AdminBundle\Route\RouteCollection;
use Sonata\AdminBundle\Show\ShowMapper;
use Splash\Admin\Fields\FormHelper;
use Splash\Admin\Filter\ListFilter;
use Splash\Admin\Filter\PrimaryFilter;
use Splash\Admin\Form\FieldsTransformer;
use Splash\Admin\Form\Type\FieldsListType;
use Splash\Admin\Model\ObjectsManager;
use Splash\Bundle\Interfaces\Connectors\PrimaryKeysInterface;
use Symfony\Component\Form\Extension\Core\Type\TextType;

/**
 * Objects Admin Class for Splash Connectors Explorer
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class ObjectsAdmin extends AbstractAdmin
{
    /**
     * @var int
     */
    protected $maxPerPage = 25;

    /**
     * Build Splash Objects Single Field Form.
     *
     * @param BaseGroupedMapper $mapper
     * @param array             $field
     *
     * @return $this
     */
    public function buildFieldForm($mapper, array $field)
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
            $field["id"],
            FormHelper::formType($field),
            $options
        );
        $mapper->end();

        if ($mapper instanceof FormMapper) {
            /** @phpstan-ignore-next-line  */
            $mapper->get($field["id"])->addModelTransformer(
                new FieldsTransformer($field["type"], !empty($field["choices"]) ? $field["choices"] : null)
            );
        }

        return $this;
    }

    /**
     * Builder Splash Object List Field Form.
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
            $list = FormHelper::isListField($field["id"]);
            if ($list) {
                $newObject[$list['listname']] = null;

                continue;
            }
            //====================================================================//
            // Add Empty Single Field to Object
            $newObject[$field["id"]] = null;
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
        // @phpstan-ignore-next-line
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
        // @phpstan-ignore-next-line
        $this
            ->getTemplateRegistry()
            ->setTemplate("edit", "@SplashAdmin/CRUD/edit_object.html.twig");

        $this->configureFields($formMapper);
    }

    /**
     * {@inheritdoc}
     */
    protected function configureListFields(ListMapper $listMapper): void
    {
        $listMapper->addIdentifier('id', TextType::class);
        //====================================================================//
        // Load Object Fields
        /** @var ObjectsManager $modelManager */
        $modelManager = $this->getModelManager();
        $objectFields = $modelManager->getObjectFields();
        //====================================================================//
        // Walk on Object Fields
        foreach ($objectFields as $field) {
            //====================================================================//
            // Filter Non Listed Fields
            if (empty($field["inlist"])) {
                continue;
            }
            //====================================================================//
            // Add Single Field to List Mapper
            $listMapper->add($field["id"], TextType::class, array(
                'label' => $field["name"]
            ));
        }
        //====================================================================//
        // Add Actions
        $listMapper
            ->add('_action', null, array(
                'actions' => array(
                    'show' => array('template' => '@SplashAdmin/Objects/show_button.html.twig'),
                    'edit' => array('template' => '@SplashAdmin/Objects/edit_button.html.twig'),
                    'delete' => array('template' => '@SplashAdmin/Objects/delete_button.html.twig'),
                )
            ))
        ;
    }

    /**
     * {@inheritdoc}
     *
     * @throws Exception
     */
    protected function configureDatagridFilters(DatagridMapper $datagridMapper): void
    {
        /** @var ObjectsManager $modelManager */
        $modelManager = $this->getModelManager();

        //====================================================================//
        // Add Text Filter
        $datagridMapper->add(
            'filter',
            ListFilter::class,
            array(
                'label' => 'Filter',
                'show_filter' => true,
                'field_type' => TextType::class,
            )
        );
        //====================================================================//
        // Load Object Fields
        $objectFields = $modelManager->getObjectFields();
        //====================================================================//
        // Walk on Object Fields to Search Primary Fields
        $hasPrimary = false;
        foreach ($objectFields as $field) {
            if (empty($field["primary"])) {
                continue;
            }
            $hasPrimary = true;
            //====================================================================//
            // Add Primary Filter
            $datagridMapper->add(
                $field["id"],
                PrimaryFilter::class,
                array(
                    'label' => '[Primary] '.$field["name"],
                    'show_filter' => true,
                    'field_type' => TextType::class,
                )
            );
        }
        if (!$hasPrimary) {
            return;
        }
        //====================================================================//
        // Check if Connector is Primary Aware
        $connector = $modelManager->getConnector();
        if (!$connector instanceof PrimaryKeysInterface) {
            throw new Exception(sprintf(
                "Your Object define a Primary Key but your Connector is not a %s",
                PrimaryKeysInterface::class
            ));
        }
    }

    /**
     * @param FormMapper|ShowMapper $mapper
     *
     * @return void
     */
    private function configureFields($mapper)
    {
        $lists = array();
        //====================================================================//
        // Load Object Fields
        /** @var ObjectsManager $modelManager */
        $modelManager = $this->getModelManager();
        $objectFields = $modelManager->getObjectFields();
        //====================================================================//
        // Walk on Object Fields
        foreach ($objectFields as $field) {
            //====================================================================//
            // Filter ReadOnly Fields
            if (!($mapper instanceof ShowMapper) && empty($field["write"])) {
                continue;
            }
            //====================================================================//
            // Add Single Fields to Mapper
            if (!FormHelper::isListField($field["type"])) {
                $this->buildFieldForm($mapper, $field);

                continue;
            }
            //====================================================================//
            // Add List Field to Buffer
            $list = FormHelper::isListField($field["id"]);
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
