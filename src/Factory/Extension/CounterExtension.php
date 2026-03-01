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

class CounterExtension implements RuntimeExtensionInterface
{
    public function processItem(Item $item): void
    {
        /** @var array<string, int|\Closure>|null $counters */
        $counters = $item->getOption('counters');

        if (null === $counters) {
            return;
        }

        /** @var array<string, int> $resolved */
        $resolved = [];
        foreach ($counters as $name => $value) {
            $resolved[$name] = $value instanceof \Closure ? $value() : $value;
        }

        $item->setExtra('counters', $resolved);
    }
}
