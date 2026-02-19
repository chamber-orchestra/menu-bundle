<?php

declare(strict_types=1);

namespace ChamberOrchestra\MenuBundle\Factory;

use ChamberOrchestra\MenuBundle\Menu\ItemInterface;

interface FactoryInterface
{
    /**
     * Creates a menu item.
     *
     * @param string $name
     * @param array  $options
     *
     * @return ItemInterface
     */
    public function createItem(string $name, array $options = [], bool $section = false): ItemInterface;
}
