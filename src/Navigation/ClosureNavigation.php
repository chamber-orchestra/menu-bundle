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

class ClosureNavigation implements NavigationInterface
{
    public function __construct(
        private readonly \Closure $callback,
        private readonly ?string $cacheKey = null,
        private readonly int $ttl = 0,
    ) {
    }

    /**
     * @param array<string, mixed> $options
     */
    public function build(MenuBuilder $builder, array $options = []): void
    {
        ($this->callback)($builder, $options);
    }

    public function getCacheKey(): string
    {
        return $this->cacheKey ?? static::class;
    }

    public function configureCacheItem(ItemInterface $item): void
    {
        $item->expiresAfter($this->ttl);
    }

    public function getCacheBeta(): ?float
    {
        return null;
    }
}
