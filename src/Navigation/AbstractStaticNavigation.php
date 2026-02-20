<?php

declare(strict_types=1);

namespace ChamberOrchestra\MenuBundle\Navigation;

use Symfony\Contracts\Cache\ItemInterface;

abstract class AbstractStaticNavigation extends AbstractNavigation
{
    public function configureCacheItem(ItemInterface $item): void
    {
        $item->expiresAfter(0);
    }
}
