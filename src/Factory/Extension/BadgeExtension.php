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

class BadgeExtension implements RuntimeExtensionInterface
{
    public function processItem(Item $item): void
    {
        $badge = $item->getOption('badge');

        if (null === $badge) {
            return;
        }

        $item->setExtra('badge', $badge instanceof \Closure ? $badge() : $badge);
    }
}
