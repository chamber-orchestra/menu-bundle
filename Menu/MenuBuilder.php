<?php

declare(strict_types=1);

namespace ChamberOrchestra\MenuBundle\Menu;

use ChamberOrchestra\MenuBundle\Exception\LogicException;
use ChamberOrchestra\MenuBundle\Factory\FactoryInterface;

class MenuBuilder implements MenuBuilderInterface
{
    private ?ItemInterface $current = null;
    private array $parents = [];
    private ItemInterface $root;

    public function __construct(private readonly FactoryInterface $factory)
    {
        $this->root = $this->current = $this->factory->createItem('root');
    }

    public function add(string $name, array $options = [], bool $prepend = false, bool $section = false): MenuBuilderInterface
    {
        $item = $this->factory->createItem($name, $options, $section);
        $this->current->add($item, $prepend);

        return $this;
    }

    public function children(): MenuBuilderInterface
    {
        $last = $this->current->getLastChild();
        if (null === $last) {
            throw new LogicException('Cannot descend into children: the current item has no children. Call add() first.');
        }

        $this->parents[] = $this->current;
        $this->current = $last;

        return $this;
    }

    public function end(): MenuBuilderInterface
    {
        if (empty($this->parents)) {
            throw new LogicException('Cannot call end(): already at root level. Check for unbalanced children()/end() calls.');
        }

        $this->current = \array_pop($this->parents);

        return $this;
    }

    public function build(): ItemInterface
    {
        return $this->root;
    }
}
