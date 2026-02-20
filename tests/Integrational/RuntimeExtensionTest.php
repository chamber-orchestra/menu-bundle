<?php

declare(strict_types=1);

namespace Tests\Integrational;

use ChamberOrchestra\MenuBundle\Factory\Extension\RuntimeExtensionInterface;
use ChamberOrchestra\MenuBundle\Factory\Factory;
use ChamberOrchestra\MenuBundle\Menu\Item;
use ChamberOrchestra\MenuBundle\Menu\MenuBuilder;
use ChamberOrchestra\MenuBundle\Navigation\AbstractCachedNavigation;
use ChamberOrchestra\MenuBundle\NavigationFactory;
use ChamberOrchestra\MenuBundle\Registry\NavigationRegistry;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Symfony\Component\Cache\Adapter\TagAwareAdapter;
use Symfony\Contracts\Cache\CacheInterface;

/**
 * Tests the post-cache RuntimeExtensionInterface pipeline.
 */
final class RuntimeExtensionTest extends TestCase
{
    private Factory $factory;
    private NavigationRegistry $registry;
    private CacheInterface $cache;

    protected function setUp(): void
    {
        $this->factory = new Factory();
        $this->registry = $this->createStub(NavigationRegistry::class);
        $this->cache = new TagAwareAdapter(new ArrayAdapter());
    }

    #[Test]
    public function runtimeExtensionAppliesBadgeToItem(): void
    {
        $nav = new class extends AbstractCachedNavigation {
            public function build(MenuBuilder $builder, array $options = []): void
            {
                $builder->add('inbox', ['label' => 'Inbox']);
            }
        };

        $runtimeExt = new class implements RuntimeExtensionInterface {
            public function processItem(Item $item): void
            {
                if ('inbox' === $item->getName()) {
                    $item->setExtra('badge', 42);
                }
            }
        };

        $navFactory = $this->makeFactory($this->cache);
        $navFactory->addRuntimeExtensions([$runtimeExt]);
        $root = $navFactory->create($nav, []);

        self::assertSame(42, $root->getFirstChild()->getBadge());
    }

    #[Test]
    public function runtimeExtensionRunsOnEveryRequest(): void
    {
        $counter = new class {
            public int $count = 0;
        };

        $nav = new class extends AbstractCachedNavigation {
            public int $buildCount = 0;

            public function build(MenuBuilder $builder, array $options = []): void
            {
                ++$this->buildCount;
                $builder->add('scores', ['label' => 'Scores']);
            }
        };

        $runtimeExt = new class($counter) implements RuntimeExtensionInterface {
            public function __construct(private readonly object $counter)
            {
            }

            public function processItem(Item $item): void
            {
                if ('scores' === $item->getName()) {
                    ++$this->counter->count;
                    $item->setExtra('badge', $this->counter->count);
                }
            }
        };

        // First request
        $factory1 = $this->makeFactory($this->cache);
        $factory1->addRuntimeExtensions([$runtimeExt]);
        $root1 = $factory1->create($nav, []);

        // Second request (new factory, same PSR-6 cache)
        $factory2 = $this->makeFactory($this->cache);
        $factory2->addRuntimeExtensions([$runtimeExt]);
        $root2 = $factory2->create($nav, []);

        self::assertSame(1, $nav->buildCount, 'Tree must be built only once (cached)');
        self::assertSame(1, $root1->getFirstChild()->getBadge());
        self::assertSame(2, $root2->getFirstChild()->getBadge());
    }

    #[Test]
    public function runtimeExtensionAppliesToNestedChildren(): void
    {
        $nav = new class extends AbstractCachedNavigation {
            public function build(MenuBuilder $builder, array $options = []): void
            {
                $builder
                    ->add('compositions', ['label' => 'Compositions'])
                    ->children()
                        ->add('rehearsals', ['label' => 'Rehearsals'])
                    ->end();
            }
        };

        $runtimeExt = new class implements RuntimeExtensionInterface {
            public function processItem(Item $item): void
            {
                if ('rehearsals' === $item->getName()) {
                    $item->setExtra('badge', 5);
                }
            }
        };

        $navFactory = $this->makeFactory();
        $navFactory->addRuntimeExtensions([$runtimeExt]);
        $root = $navFactory->create($nav, []);

        $rehearsals = $root->getFirstChild()->getFirstChild();
        self::assertSame(5, $rehearsals->getBadge());
    }

    #[Test]
    public function multipleRuntimeExtensionsAreApplied(): void
    {
        $nav = new class extends AbstractCachedNavigation {
            public function build(MenuBuilder $builder, array $options = []): void
            {
                $builder
                    ->add('inbox', ['label' => 'Inbox'])
                    ->add('instruments', ['label' => 'Instruments']);
            }
        };

        $ext1 = new class implements RuntimeExtensionInterface {
            public function processItem(Item $item): void
            {
                if ('inbox' === $item->getName()) {
                    $item->setExtra('badge', 10);
                }
            }
        };

        $ext2 = new class implements RuntimeExtensionInterface {
            public function processItem(Item $item): void
            {
                if ('instruments' === $item->getName()) {
                    $item->setExtra('badge', 7);
                }
            }
        };

        $navFactory = $this->makeFactory();
        $navFactory->addRuntimeExtensions([$ext1, $ext2]);
        $root = $navFactory->create($nav, []);

        self::assertSame(10, $root->getFirstChild()->getBadge());
        self::assertSame(7, $root->getLastChild()->getBadge());
    }

    private function makeFactory(?CacheInterface $cache = null): NavigationFactory
    {
        return new NavigationFactory($this->registry, $this->factory, $cache);
    }
}
