<?php

declare(strict_types=1);

namespace ChamberOrchestra\MenuBundle\Menu;

use ChamberOrchestra\MenuBundle\Exception\LogicException;
use ChamberOrchestra\MenuBundle\Factory\Factory;

class MenuBuilder
{
    private Item $current;
    /** @var list<Item> */
    private array $parents = [];
    private Item $root;

    public function __construct(private readonly Factory $factory)
    {
        $this->root = $this->current = $this->factory->createItem('root');
    }

    /**
     * @param array<string, mixed> $options
     */
    public function add(string $name, array $options = [], bool $prepend = false, bool $section = false): self
    {
        $item = $this->factory->createItem($name, $options, $section);
        $this->current->add($item, $prepend);

        return $this;
    }

    public function children(): self
    {
        $last = $this->current->getLastChild();
        if (null === $last) {
            throw new LogicException('Cannot descend into children: the current item has no children. Call add() first.');
        }

        $this->parents[] = $this->current;
        $this->current = $last;

        return $this;
    }

    public function end(): self
    {
        if (empty($this->parents)) {
            throw new LogicException('Cannot call end(): already at root level. Check for unbalanced children()/end() calls.');
        }

        $this->current = \array_pop($this->parents);

        return $this;
    }

    public function build(): Item
    {
        return $this->root;
    }
}
