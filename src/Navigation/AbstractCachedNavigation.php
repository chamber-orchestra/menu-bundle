<?php

declare(strict_types=1);

namespace ChamberOrchestra\MenuBundle\Navigation;

use Symfony\Contracts\Cache\ItemInterface;

abstract class AbstractCachedNavigation extends AbstractNavigation
{
    private const int DEFAULT_LIFETIME = 86400;
    private const string DEFAULT_TAG = 'chamber_orchestra_menu';

    /** @var array<string, mixed> */
    protected array $cache = [
        'tags' => [self::DEFAULT_TAG],
        'lifetime' => self::DEFAULT_LIFETIME,
    ];

    /**
     * @param array<string, mixed> $cacheOptions
     */
    public function __construct(array $cacheOptions = [])
    {
        $this->cache = \array_replace($this->cache, $cacheOptions);
    }

    public function configureCacheItem(ItemInterface $item): void
    {
        /** @var int $lifetime */
        $lifetime = $this->cache['lifetime'];
        $item->expiresAfter($lifetime);

        if (!empty($this->cache['tags'])) {
            /** @var list<string> $tags */
            $tags = $this->cache['tags'];
            $item->tag($tags);
        }
    }
}
