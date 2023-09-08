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

namespace Splash\Admin\FieldDescription;

use Sonata\AdminBundle\FieldDescription\FieldDescriptionFactoryInterface;
use Sonata\AdminBundle\FieldDescription\FieldDescriptionInterface;
use Sonata\DoctrineORMAdminBundle\FieldDescription\FieldDescription;

final class FieldDescriptionFactory implements FieldDescriptionFactoryInterface
{
    /**
     * {@inheritDoc}
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function create(string $class, string $name, array $options = array()): FieldDescriptionInterface
    {
        $fieldDescription = new FieldDescription($name);
        $fieldDescription->setName($name);
        $fieldDescription->setOptions($options);

        return $fieldDescription;
    }
}
