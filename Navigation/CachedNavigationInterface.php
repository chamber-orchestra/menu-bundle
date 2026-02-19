<?php declare(strict_types=1);

namespace ChamberOrchestra\MenuBundle\Navigation;

use Symfony\Contracts\Cache\ItemInterface;

interface CachedNavigationInterface
{
    public function getCacheKey(): string;

    public function configureCacheItem(ItemInterface $item): void;

    public function getCacheBeta(): ?float;
}