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

namespace Splash\Admin\Fields;

use ArrayObject;
use Splash\Admin\Form\Type\ImageType;
use Splash\Admin\Form\Type\MultilangType;
use Splash\Admin\Form\Type\ObjectIdType;
use Splash\Admin\Form\Type\PriceType;
use Splash\Models\Fields\FieldsManagerTrait;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\CountryType;
use Symfony\Component\Form\Extension\Core\Type\CurrencyType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\LanguageType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\UrlType;

/**
 * @abstract Helper for Rendering Fields Symfony Forms
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class FormHelper
{
    use FieldsManagerTrait;

    /**
     * @abstract    Detect Form Group for a Field
     *
     * @param ArrayObject $field
     *
     * @return string
     */
    public static function formGroup(ArrayObject $field)
    {
        if (self::isListField($field->id)) {
            return   (string) self::listName($field->id);
        }
        if (!empty($field->group) && is_scalar($field->group)) {
            return   (string) $field->group;
        }

        return "default";
    }

    /**
     * @abstract    Detect form type to Use for Editing a Field
     *
     * @param ArrayObject $field
     *
     * @return string
     *
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    public static function formType(ArrayObject $field)
    {
        //====================================================================//
        // Get Form Type
        switch (self::baseType($field->type)) {
            case SPL_T_BOOL:
                return CheckboxType::class;
            case SPL_T_INT:
                return IntegerType::class;
            case SPL_T_DOUBLE:
                return NumberType::class;
            case SPL_T_EMAIL:
                return EmailType::class;
            case SPL_T_CURRENCY:
                return CurrencyType::class;
            case SPL_T_LANG:
                return LanguageType::class;
            case SPL_T_URL:
                return UrlType::class;
            case SPL_T_COUNTRY:
                return CountryType::class;
            case SPL_T_DATE:
                return DateType::class;
            case SPL_T_DATETIME:
                return DateTimeType::class;
            case SPL_T_PRICE:
                return PriceType::class;
            case SPL_T_ID:
                return ObjectIdType::class;
            case SPL_T_IMG:
                return ImageType::class;
            case SPL_T_MVARCHAR:
            case SPL_T_MTEXT:
                return MultilangType::class;
            case SPL_T_VARCHAR:
            case SPL_T_TEXT:
                return empty($field->choices) ? TextType::class : ChoiceType::class;
        }

        return TextType::class;
    }

    /**
     * @abstract Get Form Options for Renderieng a Field Form
     *
     * @param ArrayObject $field
     *
     * @return array
     */
    public static function formOptions(ArrayObject $field)
    {
        switch (FormHelper::baseType($field->type)) {
            case SPL_T_VARCHAR:
            case SPL_T_TEXT:
                if (empty($field->choices)) {
                    $options = array();

                    break;
                }
                $options = array(
                    'choices' => self::toFormChoice($field->choices),
                );

                break;
            case SPL_T_DATE:
            case SPL_T_DATETIME:
                $options = array(
                    'widget' => 'single_text',
                    'input' => 'string',
                );

                break;
            case SPL_T_ID:
                $objectType = self::isIdField($field->type)["ObjectType"];
                //====================================================================//
                // Detect Lists
                if (self::isListField($objectType)) {
                    $objectType = self::isListField($objectType)["fieldname"];
                }
                $options = array(
                    'object_type' => $objectType,
                );

                break;
            default:
                $options = array();

                break;
        }

        $extra = isset($field->options["language"]) ? " (".$field->options["language"].")" : null;

        return array_replace_recursive($options, array(
            'label' => html_entity_decode($field->name).$extra,
            //====================================================================//
            // Manage Required Tag
            "required" => !empty($field->required),
        ));
    }

    /**
     * @abstract Build Field Show Options
     *
     * @param array|ArrayObject $field
     * @param bool              $isList
     *
     * @return array
     */
    public static function showOptions($field, bool $isList = null)
    {
        return array(
            //====================================================================//
            // Add List Flag
            "splash_is_list" => !empty($isList),
            //====================================================================//
            // Add Fields Informations
            "splash_field" => $field,
            //====================================================================//
            // Specify Sonata to Render Splash Specific Template
            "template" => '@SplashAdmin/CRUD/base_show_field.html.twig',
        );
    }

    /**
     * @abstract Transform Splash choices array to Symfony Choices
     *
     * @param array $fieldChoices
     *
     * @return array|ArrayObject
     */
    private static function toFormChoice($fieldChoices)
    {
        $response = array();
        foreach ($fieldChoices as $choice) {
            $response[$choice["value"]] = $choice["key"];
        }

        return $response;
    }
}
