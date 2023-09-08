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

use Sonata\AdminBundle\Datagrid\ProxyQueryInterface;
use Sonata\AdminBundle\Filter\Filter;
use Sonata\AdminBundle\Filter\Model\FilterData;
use Splash\Admin\Datagrid\SplashQuery;

/**
 * Splash Webservice Objects Lists String Filter
 */
class ListFilter extends Filter
{
    /**
     * {@inheritDoc}
     */
    public function apply(ProxyQueryInterface $query, FilterData $filterData): void
    {
        if (!$filterData->hasValue() || !($query instanceof SplashQuery)) {
            return;
        }
        $value = $filterData->getValue();
        if ($value && is_scalar($value)) {
            $query->setFilterBy((string) $value);
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

    public function getFormOptions(): array
    {
        return array();
    }
}
