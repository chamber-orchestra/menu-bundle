<?php

declare(strict_types=1);

namespace ChamberOrchestra\MenuBundle\Factory;

use ChamberOrchestra\MenuBundle\Factory\Extension\ExtensionInterface;
use ChamberOrchestra\MenuBundle\Menu\Item;

class Factory
{
    /** @var array<int, list<ExtensionInterface>> */
    private array $extensions = [];
    /** @var list<ExtensionInterface>|null */
    private ?array $sorted = null;

    /**
     * @param array<string, mixed> $options
     */
    public function createItem(string $name, array $options = [], bool $section = false): Item
    {
        $options['key'] = $name;
        foreach ($this->getExtensions() as $extension) {
            $options = $extension->buildOptions($options);
        }

        return new Item($name, $options, $section);
    }

    public function addExtension(ExtensionInterface $extension, int $priority = 0): void
    {
        $this->extensions[$priority][] = $extension;
        $this->sorted = null;
    }

    /**
     * @param iterable<ExtensionInterface> $extensions
     */
    public function addExtensions(iterable $extensions): void
    {
        foreach ($extensions as $extension) {
            $this->addExtension($extension);
        }
    }

    /**
     * @return list<ExtensionInterface>
     */
    private function getExtensions(): array
    {
        if (null === $this->sorted) {
            \krsort($this->extensions);
            $this->sorted = !empty($this->extensions) ? \array_merge(...$this->extensions) : [];
        }

        return $this->sorted;
    }
}
