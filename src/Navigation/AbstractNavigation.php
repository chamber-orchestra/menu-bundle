<?php

declare(strict_types=1);

/*
 * This file is part of the ChamberOrchestra package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ChamberOrchestra\MenuBundle\Navigation;

use ChamberOrchestra\MenuBundle\Menu\MenuBuilder;
use Symfony\Contracts\Cache\ItemInterface;

abstract class AbstractNavigation implements NavigationInterface
{
    /**
     * @param array<string, mixed> $options
     */
    abstract public function build(MenuBuilder $builder, array $options = []): void;

    public function isCacheable(): bool
    {
        return false;
    }

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
