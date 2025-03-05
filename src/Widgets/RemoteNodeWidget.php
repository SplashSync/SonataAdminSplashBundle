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

namespace Splash\Admin\Widgets;

use BadPixxel\Widgets\Blocks\Basics\TextBlock;
use BadPixxel\Widgets\Interfaces\Widgets\ConfigurableWidgetInterface;
use BadPixxel\Widgets\Models\AbstractWidget;
use BadPixxel\Widgets\Services\Blocks\BlockResolver;
use BadPixxel\Widgets\Widgets\Descriptor\SimpleDescriptor;
use Splash\Admin\Fields\FormHelper;
use Splash\Bundle\Models\AbstractConnector;
use Splash\Bundle\Services\ConnectorsManager;
use Symfony\Component\Form\FormBuilderInterface;
use Webmozart\Assert\Assert;

/**
 * Splash Remote Node Parsing Widget
 */
class RemoteNodeWidget extends AbstractWidget implements ConfigurableWidgetInterface
{
    public function __construct(
        private readonly ConnectorsManager $manager,
        private readonly BlockResolver $blockResolver,
    ) {
        parent::__construct();
    }

    /**
     * @inheritDoc
     */
    public function getDescriptor(): SimpleDescriptor
    {
        //==============================================================================
        // Fetch Description from Connector
        $widgetDesc = $this->getConnector()->getWidgetDescription($this->getWidgetType());

        //==============================================================================
        // Build Descriptor with received Information
        return new SimpleDescriptor(
            title: $widgetDesc["name"] ?? "Widget Name",
            description: $widgetDesc["description"] ?? "Demo Widget for Splash Nodes",
            icon: $widgetDesc["icon"] ?? "fa fa-fw fa-font",
            origin: "Splash Widgets"
        );
    }

    /**
     * Build Block
     */
    public function build() : void
    {
        //==============================================================================
        // Fetch Contents from Connector
        $contents = $this->getConnector()->getWidgetContents(
            $this->getWidgetType(),
            $this->getParameters()
        );
        $widgetBlocks = $contents["blocks"] ?? array();
        Assert::isArray($widgetBlocks);
        //==============================================================================
        // No Contents => Show Error Message
        if (empty($widgetBlocks)) {
            $errorBlock = new TextBlock();
            $errorBlock->setText("No Contents Received from Server.");

            $this->addBlock($errorBlock);

            return;
        }
        //==============================================================================
        // Walk on received Blocks
        foreach ($widgetBlocks as $widgetBlock) {
            //==============================================================================
            // Resolve Block Type
            Assert::stringNotEmpty($widgetBlock["type"]);
            if (!$block = $this->blockResolver->findByType($widgetBlock["type"])) {
                continue;
            }
            //==============================================================================
            // Configure Block
            Assert::isArray(
                $data = $widgetBlock["data"] ?? array(),
                "Block Data is not an Array"
            );
            Assert::isArray(
                $options = $widgetBlock["options"] ?? array(),
                "Block Options is not an Array"
            );
            $block
                ->setData($data)
                ->mergeOptions($options)
            ;
            //==============================================================================
            // Add Block to Widget
            $this->addBlock($block);
        }
    }

    /**
     * @inheritdoc
     */
    public function buildForm(FormBuilderInterface $builder) : void
    {
        //==============================================================================
        // Fetch Description from Connector
        $widgetDesc = $this->getConnector()->getWidgetDescription($this->getWidgetType());
        //==============================================================================
        // Verify Parameter Fields are Available
        $parameters = $widgetDesc["parameters"] ?? array();
        if (empty($parameters)) {
            return;
        }
        Assert::isArray($parameters);
        //==============================================================================
        // Add Parameters to Widget Form
        foreach ($parameters as $field) {
            //====================================================================//
            // Add Single Fields to Form
            $builder->add(
                $field['id'],
                FormHelper::formType($field),
                array_replace_recursive(
                    FormHelper::formOptions($field),
                    array(
                        "row_attr" => array("class" => "col-12"),
                        "property_path" => "parameters[".$field['id']."]",
                    )
                )
            );
        }
    }

    /**
     * Get Connector
     */
    private function getConnector(): AbstractConnector
    {
        Assert::stringNotEmpty($wsId = $this->getParameter("webserviceId"));

        return $this->manager->get($wsId);
    }

    /**
     * Get Connector
     */
    private function getWidgetType(): string
    {
        Assert::stringNotEmpty($widgetType = $this->getParameter("widgetType"));

        return $widgetType;
    }
}
