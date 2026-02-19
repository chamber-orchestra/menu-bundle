<?php

declare(strict_types=1);

namespace ChamberOrchestra\MenuBundle\Navigation;

use Symfony\Contracts\Cache\ItemInterface;

abstract class AbstractCachedNavigation extends AbstractNavigation implements CachedNavigationInterface
{
    protected array $cache = [
        'tags' => ['navigation'],
        'lifetime' => 24 * 60 * 60,
    ];

    public function __construct(array $cacheOptions = [])
    {
        $this->cache = \array_replace($this->cache, $cacheOptions);
    }

    public function getCacheKey(): string
    {
        return static::class;
    }

    public function configureCacheItem(ItemInterface $item): void
    {
        $item->expiresAfter($this->cache['lifetime']);

        if (!empty($this->cache['tags'])) {
            $item->tag($this->cache['tags']);
        }
    }

    public function getCacheBeta(): ?float
    {
        return .0;
    }
}
