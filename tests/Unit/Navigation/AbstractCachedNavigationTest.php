<?php

declare(strict_types=1);

/*
 * This file is part of the ChamberOrchestra package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Tests\Unit\Navigation;

use ChamberOrchestra\MenuBundle\Menu\MenuBuilder;
use ChamberOrchestra\MenuBundle\Navigation\AbstractCachedNavigation;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Symfony\Contracts\Cache\ItemInterface;

final class AbstractCachedNavigationTest extends TestCase
{
    #[Test]
    public function getCacheKeyReturnsClassName(): void
    {
        $nav = $this->makeNav();

        self::assertSame($nav::class, $nav->getCacheKey());
    }

    #[Test]
    public function getCacheBetaReturnsNull(): void
    {
        self::assertNull($this->makeNav()->getCacheBeta());
    }

    #[Test]
    public function defaultLifetimeIs24Hours(): void
    {
        $item = $this->createMock(ItemInterface::class);
        $item->expects(self::once())->method('expiresAfter')->with(24 * 60 * 60);
        $item->method('tag');

        $this->makeNav()->configureCacheItem($item);
    }

    #[Test]
    public function configureCacheItemAppliesDefaultNavigationTag(): void
    {
        $item = $this->createMock(ItemInterface::class);
        $item->method('expiresAfter');
        $item->expects(self::once())->method('tag')->with(['chamber_orchestra_menu']);

        $this->makeNav()->configureCacheItem($item);
    }

    #[Test]
    public function customLifetimeIsApplied(): void
    {
        $item = $this->createMock(ItemInterface::class);
        $item->expects(self::once())->method('expiresAfter')->with(3600);
        $item->method('tag');

        $this->makeNav(['lifetime' => 3600])->configureCacheItem($item);
    }

    #[Test]
    public function customTagsAreApplied(): void
    {
        $item = $this->createMock(ItemInterface::class);
        $item->method('expiresAfter');
        $item->expects(self::once())->method('tag')->with(['menu', 'sidebar']);

        $this->makeNav(['tags' => ['menu', 'sidebar']])->configureCacheItem($item);
    }

    #[Test]
    public function emptyTagsSkipsTagCall(): void
    {
        $item = $this->createMock(ItemInterface::class);
        $item->method('expiresAfter');
        $item->expects(self::never())->method('tag');

        $this->makeNav(['tags' => []])->configureCacheItem($item);
    }

    #[Test]
    public function customOptionsAreMergedWithDefaults(): void
    {
        // Providing only lifetime should not reset tags to empty
        $nav = $this->makeNav(['lifetime' => 7200]);

        $item = $this->createMock(ItemInterface::class);
        $item->expects(self::once())->method('expiresAfter')->with(7200);
        $item->expects(self::once())->method('tag')->with(['chamber_orchestra_menu']); // default tags preserved

        $nav->configureCacheItem($item);
    }

    /**
     * @param array<string, mixed> $cacheOptions
     */
    private function makeNav(array $cacheOptions = []): AbstractCachedNavigation
    {
        return new class($cacheOptions) extends AbstractCachedNavigation {
            public function build(MenuBuilder $builder, array $options = []): void
            {
            }
        };
    }
}
