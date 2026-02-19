<?php

declare(strict_types=1);

namespace ChamberOrchestra\MenuBundle\Navigation;

use ChamberOrchestra\MenuBundle\Menu\MenuBuilderInterface;

interface NavigationInterface
{
    public function build(MenuBuilderInterface $builder, array $options = []): void;
}
