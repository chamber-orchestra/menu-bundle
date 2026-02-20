<?php

declare(strict_types=1);

namespace ChamberOrchestra\MenuBundle\Navigation;

use ChamberOrchestra\MenuBundle\Menu\MenuBuilder;
use Symfony\Contracts\Cache\ItemInterface;

interface NavigationInterface
{
    /**
     * @param array<string, mixed> $options
     */
    public function build(MenuBuilder $builder, array $options = []): void;

    public function getCacheKey(): string;

    public function configureCacheItem(ItemInterface $item): void;

    public function getCacheBeta(): ?float;
}
