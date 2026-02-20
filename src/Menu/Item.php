<?php

declare(strict_types=1);

namespace ChamberOrchestra\MenuBundle\Menu;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

/**
 * @implements \IteratorAggregate<int, Item>
 */
class Item implements \Countable, \IteratorAggregate
{
    /** @var ArrayCollection<int, Item> */
    private ArrayCollection $children;
    private bool $section;
    /** @var array<string, mixed> */
    private array $options = [
        'uri' => null,
        'attributes' => [],
    ];

    /**
     * @param array<string, mixed> $options
     */
    public function __construct(private string $name, array $options = [], bool $section = false)
    {
        $this->children = new ArrayCollection();
        /** @var array<string, mixed> $merged */
        $merged = \array_replace_recursive($this->options, $options);
        $this->options = $merged;
        $this->section = $section;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getLabel(): string
    {
        /** @var string $label */
        $label = $this->options['label'] ?? '';

        return $label;
    }

    public function getUri(): ?string
    {
        $uri = $this->options['uri'] ?? null;

        /** @var string|null $result */
        $result = $uri;

        return $result;
    }

    /**
     * @return list<string>
     */
    public function getRoles(): array
    {
        /** @var list<string> $roles */
        $roles = $this->options['roles'] ?? [];

        return $roles;
    }

    public function getOption(string $name, mixed $default = null): mixed
    {
        return \array_key_exists($name, $this->options) ? $this->options[$name] : $default;
    }

    public function add(self $item, bool $prepend = false): self
    {
        if (true === $prepend) {
            /** @var list<Item> $collection */
            $collection = $this->children->toArray();
            \array_unshift($collection, $item);
            $this->children = new ArrayCollection($collection);

            return $this;
        }

        $this->children->add($item);

        return $this;
    }

    /**
     * @return Collection<int, Item>
     */
    public function getChildren(): Collection
    {
        return $this->children;
    }

    public function getFirstChild(): ?self
    {
        return $this->children->first() ?: null;
    }

    public function getLastChild(): ?self
    {
        return $this->children->last() ?: null;
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

    public function setExtra(string $key, mixed $value): self
    {
        /** @var array<string, mixed> $extras */
        $extras = $this->options['extras'] ?? [];
        $extras[$key] = $value;
        $this->options['extras'] = $extras;

        return $this;
    }

    public function getBadge(): ?int
    {
        /** @var array<string, mixed> $extras */
        $extras = $this->options['extras'] ?? [];
        $badge = $extras['badge'] ?? null;

        return \is_int($badge) ? $badge : null;
    }

    /**
     * @return array<string, mixed>
     */
    public function __serialize(): array
    {
        return [
            'name' => $this->name,
            'children' => $this->children->toArray(),
            'options' => $this->options,
            'section' => $this->section,
        ];
    }

    /**
     * @param array<string, mixed> $data
     */
    public function __unserialize(array $data): void
    {
        /** @var string $name */
        $name = $data['name'];
        $this->name = $name;
        /** @var list<Item> $children */
        $children = $data['children'];
        $this->children = new ArrayCollection($children);
        /** @var array<string, mixed> $options */
        $options = $data['options'];
        $this->options = $options;
        /** @var bool $section */
        $section = $data['section'];
        $this->section = $section;
    }
}
