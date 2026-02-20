<?php

declare(strict_types=1);

namespace ChamberOrchestra\MenuBundle\Navigation;

use ChamberOrchestra\MenuBundle\Menu\MenuBuilder;
use Symfony\Contracts\Cache\ItemInterface;

class ClosureNavigation implements NavigationInterface
{
    public function __construct(private readonly \Closure $callback)
    {
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
