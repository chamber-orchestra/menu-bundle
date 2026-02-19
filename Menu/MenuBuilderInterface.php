<?php

declare(strict_types=1);

namespace ChamberOrchestra\MenuBundle\Menu;

interface MenuBuilderInterface
{
    public function add(string $name, array $options = [], bool $prepend = false, bool $section = false): MenuBuilderInterface;

    public function children(): MenuBuilderInterface;

    public function end(): MenuBuilderInterface;

    public function build(): ItemInterface;
}
