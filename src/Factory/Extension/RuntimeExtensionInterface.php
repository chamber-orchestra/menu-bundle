<?php

declare(strict_types=1);

/*
 * This file is part of the ChamberOrchestra package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ChamberOrchestra\MenuBundle\Factory\Extension;

use ChamberOrchestra\MenuBundle\Menu\Item;

interface RuntimeExtensionInterface
{
    /**
     * Processes an item after cache retrieval, applying fresh dynamic data.
     */
    public function processItem(Item $item): void;
}
