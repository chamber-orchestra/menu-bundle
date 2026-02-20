<?php

declare(strict_types=1);

namespace ChamberOrchestra\MenuBundle\Navigation;

use ChamberOrchestra\MenuBundle\Menu\MenuBuilder;
use Symfony\Contracts\Cache\ItemInterface;

abstract class AbstractNavigation implements NavigationInterface
{
    /**
     * @param array<string, mixed> $options
     */
    abstract public function build(MenuBuilder $builder, array $options = []): void;

    public function getCacheKey(): string
    {
        return static::class;
    }

    public function configureCacheItem(ItemInterface $item): void
    {
        $item->expiresAfter(0);
    }

    public function getCacheBeta(): ?float
    {
        return null;
    }
}
