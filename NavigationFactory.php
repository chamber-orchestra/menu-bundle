<?php

declare(strict_types=1);

namespace ChamberOrchestra\MenuBundle;

use ChamberOrchestra\MenuBundle\Factory\FactoryInterface;
use ChamberOrchestra\MenuBundle\Menu\MenuBuilder;
use ChamberOrchestra\MenuBundle\Navigation\CachedNavigationInterface;
use ChamberOrchestra\MenuBundle\Navigation\NavigationInterface;
use ChamberOrchestra\MenuBundle\Registry\NavigationRegistry;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;

class NavigationFactory
{
    private array $built = [];
    private array $options = [
        'namespace' => '$NAVIGATION$',
    ];

    public function __construct(
        private readonly NavigationRegistry $registry,
        private readonly FactoryInterface $factory,
        private readonly ?CacheInterface $cache,
        array $options = []
    )
    {
        $this->options = \array_replace($this->options, $options);
    }

    /**
     * @throws \Psr\Cache\InvalidArgumentException
     */
    public function create(NavigationInterface|string $nav, array $options): Menu\ItemInterface
    {
        if (\is_string($nav)) {
            $nav = $this->registry->get($nav);
        }

        $isCached = $nav instanceof CachedNavigationInterface;
        $key = $isCached ? $nav::class : null;

        if ($isCached && isset($this->built[$key])) {
            return $this->built[$key];
        }

        $build = function () use ($nav, $options): Menu\ItemInterface {
            $builder = $this->createNewBuilder();
            $nav->build($builder, $options);

            return $builder->build();
        };

        if (null !== $this->cache && $isCached) {
            $cached = $this->cache->get(
                $this->createCacheKey($nav),
                function (ItemInterface $item) use ($build, $nav): Menu\ItemInterface {
                    $nav->configureCacheItem($item);

                    return $build();
                },
                $nav->getCacheBeta());

            return $this->built[$key] = $cached;
        }

        $built = $build();
        if ($isCached) {
            $this->built[$key] = $built;
        }

        return $built;
    }

    private function createNewBuilder(): MenuBuilder
    {
        return new MenuBuilder($this->factory);
    }

    private function createCacheKey(CachedNavigationInterface $nav): string
    {
        return $this->sanitizeCacheKeyPart($this->options['namespace'])
               .$this->sanitizeCacheKeyPart($nav::class)
               .$this->sanitizeCacheKeyPart($nav->getCacheKey());
    }

    private function sanitizeCacheKeyPart(string $cacheKeyPart): string
    {
        return \str_replace(['.', '\\', '/'], ['_', '.', '.'], $cacheKeyPart);
    }
}
