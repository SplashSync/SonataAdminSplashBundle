<?php

declare(strict_types=1);

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

namespace Splash\Admin\Filter;

use Sonata\AdminBundle\Datagrid\ProxyQueryInterface as BaseProxyQueryInterface;
use Sonata\AdminBundle\Filter\Filter;
use Sonata\AdminBundle\Form\Type\Filter\ChoiceType;
use Splash\Admin\Datagrid\SplashQuery;

/**
 * Splash Webservice Objects Lists String Filter
 */
class ListFilter extends Filter
{
    /**
     * @param BaseProxyQueryInterface $query
     * @param string                  $alias
     * @param string                  $field
     * @param mixed                   $data
     *
     * @return void
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function filter(BaseProxyQueryInterface $query, $alias, $field, $data)
    {
    }

    /**
     * @param BaseProxyQueryInterface $query
     * @param array|scalar            $filterData
     *
     * @return void
     */
    public function apply($query, $filterData)
    {
        if (\is_array($filterData) && \array_key_exists('value', $filterData)) {
            if ($query instanceof SplashQuery) {
                $query->setFilterBy($filterData['value']);
            }
        }
    }

    /**
     * @return array
     */
    public function getDefaultOptions(): array
    {
        return array(
            // NEXT_MAJOR: Remove the "format" option.
            'format' => '%%%s%%',
            // NEXT_MAJOR: Remove the "case_sensitive" option.
            'case_sensitive' => null,
            // NEXT_MAJOR: Use `false` as default value for the "force_case_insensitivity" option.
            'force_case_insensitivity' => null,
            'allow_empty' => false,
            'global_search' => false,
        );
    }

    /**
     * @return array
     */
    public function getRenderSettings(): array
    {
        return array(ChoiceType::class, array(
            'field_type' => $this->getFieldType(),
            'field_options' => $this->getFieldOptions(),
            'label' => $this->getLabel(),
        ));
    }
}
