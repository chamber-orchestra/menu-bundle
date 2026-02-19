<?php

declare(strict_types=1);

namespace ChamberOrchestra\MenuBundle\Registry;

use ChamberOrchestra\MenuBundle\Navigation\NavigationInterface;

interface NavigationRegistryInterface
{
    /**
     * Retrieves a menu by its name.
     *
     * @param string $name
     *
     * @return NavigationInterface
     */
    public function get(string $name): NavigationInterface;
}
