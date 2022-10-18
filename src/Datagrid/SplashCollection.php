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

namespace Splash\Admin\Datagrid;

use Doctrine\Common\Collections\ArrayCollection;

class SplashCollection extends ArrayCollection
{
    /**
     * @var null|int
     */
    private ?int $total = null;

    /**
     * Force total Number of Results
     *
     * @param null|int $total
     *
     * @return SplashCollection
     */
    public function setTotalResults(?int $total): self
    {
        $this->total = $total;

        return $this;
    }

    /**
     * {@inheritDoc}
     *
     * @return int
     */
    public function count(): int
    {
        return $this->total ?? parent::count();
    }
}
