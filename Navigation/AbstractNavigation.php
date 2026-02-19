<?php

declare(strict_types=1);

namespace ChamberOrchestra\MenuBundle\Navigation;

use ChamberOrchestra\MenuBundle\Menu\MenuBuilderInterface;

abstract class AbstractNavigation implements NavigationInterface
{
    abstract public function build(MenuBuilderInterface $builder, array $options = []): void;
}
