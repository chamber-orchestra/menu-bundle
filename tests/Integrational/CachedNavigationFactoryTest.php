<?php

declare(strict_types=1);

namespace Tests\Integrational;

use ChamberOrchestra\MenuBundle\Factory\Factory;
use ChamberOrchestra\MenuBundle\Menu\MenuBuilderInterface;
use ChamberOrchestra\MenuBundle\Navigation\AbstractCachedNavigation;
use ChamberOrchestra\MenuBundle\Navigation\AbstractNavigation;
use ChamberOrchestra\MenuBundle\NavigationFactory;
use ChamberOrchestra\MenuBundle\Registry\NavigationRegistry;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Symfony\Component\Cache\Adapter\TagAwareAdapter;
use Symfony\Contracts\Cache\CacheInterface;

/**
 * Tests NavigationFactory with real PSR-6 ArrayAdapter cache.
 */
final class CachedNavigationFactoryTest extends TestCase
{
    private Factory $factory;
    private CacheInterface $cache;
    private NavigationRegistry $registry;

    protected function setUp(): void
    {
        $this->factory = new Factory();
        $this->cache = new TagAwareAdapter(new ArrayAdapter());
        $this->registry = $this->createStub(NavigationRegistry::class);
    }

    #[Test]
    public function cachedNavigationDedupedWithinSameFactoryInstance(): void
    {
        $nav = $this->makeCachedNav();

        $factory = $this->makeFactory(null); // no PSR-6 cache
        $factory->create($nav, []);
        $factory->create($nav, []);

        self::assertSame(1, $nav->buildCount, 'Within-request dedup must work even without PSR-6');
    }

    #[Test]
    public function cachedNavigationServedFromPsrCacheOnSubsequentRequests(): void
    {
        $nav = $this->makeCachedNav();

        // Simulate two separate requests (two factory instances, shared PSR-6 cache)
        $this->makeFactory($this->cache)->create($nav, []);
        $this->makeFactory($this->cache)->create($nav, []);

        self::assertSame(1, $nav->buildCount, 'PSR-6 cache must serve the tree on the second request');
    }

    #[Test]
    public function nonCachedNavigationIsAlwaysRebuilt(): void
    {
        $nav = new class extends AbstractNavigation {
            public int $buildCount = 0;

            public function build(MenuBuilderInterface $builder, array $options = []): void
            {
                ++$this->buildCount;
                $builder->add('item');
            }
        };

        $factory = $this->makeFactory($this->cache);
        $factory->create($nav, []);
        $factory->create($nav, []);

        self::assertSame(2, $nav->buildCount);
    }

    #[Test]
    public function psrCachePreservesItemTreeStructure(): void
    {
        $nav = new class extends AbstractCachedNavigation {
            public function build(MenuBuilderInterface $builder, array $options = []): void
            {
                $builder
                    ->add('parent', ['label' => 'Parent'], section: true)
                    ->children()
                        ->add('child', ['label' => 'Child'])
                    ->end();
            }
        };

        // First request: builds and caches
        $this->makeFactory($this->cache)->create($nav, []);

        // Second request: deserializes from cache
        $restored = $this->makeFactory($this->cache)->create($nav, []);

        $parent = $restored->getFirstChild();
        self::assertSame('Parent', $parent->getLabel());
        self::assertTrue($parent->isSection());
        self::assertSame('child', $parent->getFirstChild()->getName());
    }

    #[Test]
    public function differentCachedNavigationClassesAreCachedSeparately(): void
    {
        $nav1 = new class extends AbstractCachedNavigation {
            public int $buildCount = 0;

            public function build(MenuBuilderInterface $builder, array $options = []): void
            {
                ++$this->buildCount;
                $builder->add('nav1_item');
            }
        };

        $nav2 = new class extends AbstractCachedNavigation {
            public int $buildCount = 0;

            public function build(MenuBuilderInterface $builder, array $options = []): void
            {
                ++$this->buildCount;
                $builder->add('nav2_item');
            }
        };

        $factory = $this->makeFactory($this->cache);
        $root1 = $factory->create($nav1, []);
        $root2 = $factory->create($nav2, []);

        self::assertSame('nav1_item', $root1->getFirstChild()->getName());
        self::assertSame('nav2_item', $root2->getFirstChild()->getName());
    }

    #[Test]
    public function navigatingWithoutPsrCacheStillDedupesWithinRequest(): void
    {
        $nav = $this->makeCachedNav();
        $factoryNocache = $this->makeFactory(null);

        $root1 = $factoryNocache->create($nav, []);
        $root2 = $factoryNocache->create($nav, []);

        self::assertSame($root1, $root2, 'Same item instance returned from in-memory dedup');
    }

    private function makeCachedNav(): AbstractCachedNavigation
    {
        return new class extends AbstractCachedNavigation {
            public int $buildCount = 0;

            public function build(MenuBuilderInterface $builder, array $options = []): void
            {
                ++$this->buildCount;
                $builder->add('item', ['label' => 'Item']);
            }
        };
    }

    private function makeFactory(?CacheInterface $cache): NavigationFactory
    {
        return new NavigationFactory($this->registry, $this->factory, $cache);
    }
}
