<?php

declare(strict_types=1);

/*
 * This file is part of the ChamberOrchestra package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ChamberOrchestra\MenuBundle;

use ChamberOrchestra\MenuBundle\Factory\Extension\RuntimeExtensionInterface;
use ChamberOrchestra\MenuBundle\Factory\Factory;
use ChamberOrchestra\MenuBundle\Menu\Item;
use ChamberOrchestra\MenuBundle\Menu\MenuBuilder;
use ChamberOrchestra\MenuBundle\Navigation\NavigationInterface;
use ChamberOrchestra\MenuBundle\Registry\NavigationRegistry;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Symfony\Component\Cache\Adapter\TagAwareAdapter;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;

class NavigationFactory
{
    /** @var array<string, Item> */
    private array $built = [];
    /** @var array<string, mixed> */
    private array $options = [
        'namespace' => '$NAVIGATION$',
    ];
    private readonly CacheInterface $cache;
    /** @var list<RuntimeExtensionInterface> */
    private array $runtimeExtensions = [];

    /**
     * @param array<string, mixed> $options
     */
    public function __construct(
        private readonly NavigationRegistry $registry,
        private readonly Factory $factory,
        ?CacheInterface $cache = null,
        array $options = [],
    ) {
        $this->cache = $cache ?? new TagAwareAdapter(new ArrayAdapter());
        $this->options = \array_replace($this->options, $options);
    }

    /**
     * @param array<string, mixed> $options
     *
     * @throws \Psr\Cache\InvalidArgumentException
     */
    public function create(NavigationInterface|string $nav, array $options): Item
    {
        if (\is_string($nav)) {
            $nav = $this->registry->get($nav);
        }

        $key = $nav::class;

        if (isset($this->built[$key])) {
            return $this->built[$key];
        }

        $cached = $this->cache->get(
            $this->createCacheKey($nav),
            function (ItemInterface $item) use ($nav, $options): Item {
                $nav->configureCacheItem($item);

                $builder = $this->createNewBuilder();
                $nav->build($builder, $options);

                return $builder->build();
            },
            $nav->getCacheBeta(),
        );

        $this->applyRuntimeExtensions($cached);

        return $this->built[$key] = $cached;
    }

    /**
     * @param iterable<RuntimeExtensionInterface> $extensions
     */
    public function addRuntimeExtensions(iterable $extensions): void
    {
        foreach ($extensions as $extension) {
            $this->runtimeExtensions[] = $extension;
        }
    }

    private function applyRuntimeExtensions(Item $item): void
    {
        foreach ($this->runtimeExtensions as $extension) {
            $extension->processItem($item);
        }

        foreach ($item as $child) {
            $this->applyRuntimeExtensions($child);
        }
    }

    private function createNewBuilder(): MenuBuilder
    {
        return new MenuBuilder($this->factory);
    }

    private function createCacheKey(NavigationInterface $nav): string
    {
        /** @var string $namespace */
        $namespace = $this->options['namespace'];

        return $this->sanitizeCacheKeyPart($namespace)
               .$this->sanitizeCacheKeyPart($nav::class)
               .$this->sanitizeCacheKeyPart($nav->getCacheKey());
    }

    private function sanitizeCacheKeyPart(string $cacheKeyPart): string
    {
        return \str_replace(
            ['.', '\\', '/', '{', '}', '(', ')', '@', ':', ' ', '$'],
            ['_', '.', '.', '-', '-', '-', '-', '-', '-', '-', '-'],
            $cacheKeyPart,
        );
    }
}
