<?php

declare(strict_types=1);

namespace ChamberOrchestra\MenuBundle\Navigation;

use ChamberOrchestra\MenuBundle\Menu\MenuBuilderInterface;

class ClosureNavigation implements NavigationInterface
{
    private \Closure $callback;

    public function __construct(callable $callback)
    {
        $this->callback = \Closure::fromCallable($callback);
    }

    public function build(MenuBuilderInterface $builder, array $options = []): void
    {
        ($this->callback)($builder, $options);
    }
}
