<?php

declare(strict_types=1);

namespace ChamberOrchestra\MenuBundle\Factory\Extension;

use ChamberOrchestra\MenuBundle\Menu\Item;

interface RuntimeExtensionInterface
{
    /**
     * Processes an item after cache retrieval, applying fresh dynamic data.
     */
    public function processItem(Item $item): void;
}
