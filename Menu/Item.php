<?php

declare(strict_types=1);

namespace ChamberOrchestra\MenuBundle\Menu;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

/**
 * Default implementation of the ItemInterface.
 */
class Item implements ItemInterface
{
    private ArrayCollection $children;
    private bool $section;
    private array $options = [
        'uri' => null,
        'attributes' => [],
    ];

    public function __construct(private string $name, array $options = [], bool $section = false)
    {
        $this->children = new ArrayCollection();
        $this->options = \array_replace_recursive($this->options, $options);
        $this->section = $section;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getLabel(): string
    {
        return $this->options['label'] ?? '';
    }

    public function getUri(): ?string
    {
        return $this->options['uri'] ?? null;
    }

    public function getRoles(): array
    {
        return $this->options['roles'] ?? [];
    }

    public function getOption(string $name, mixed $default = null): mixed
    {
        return \array_key_exists($name, $this->options) ? $this->options[$name] : $default;
    }

    public function add(ItemInterface $item, bool $prepend = false): ItemInterface
    {
        if (true === $prepend) {
            $collection = $this->children->toArray();
            \array_unshift($collection, $item);
            $this->children = new ArrayCollection($collection);

            return $this;
        }

        $this->children->add($item);

        return $this;
    }

    public function getChildren(): Collection
    {
        return $this->children;
    }

    public function getFirstChild(): ?ItemInterface
    {
        $first = $this->children->first();

        return false !== $first ? $first : null;
    }

    public function getLastChild(): ?ItemInterface
    {
        $last = $this->children->last();

        return false !== $last ? $last : null;
    }

    public function count(): int
    {
        return $this->children->count();
    }

    public function getIterator(): \Traversable
    {
        return $this->children->getIterator();
    }

    public function isSection(): bool
    {
        return $this->section;
    }

    public function __serialize(): array
    {
        return [
            'name'     => $this->name,
            'children' => $this->children->toArray(),
            'options'  => $this->options,
            'section'  => $this->section,
        ];
    }

    public function __unserialize(array $data): void
    {
        $this->name     = $data['name'];
        $this->children = new ArrayCollection($data['children']);
        $this->options  = $data['options'];
        $this->section  = $data['section'];
    }
}
