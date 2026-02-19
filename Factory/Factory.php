<?php

declare(strict_types=1);

namespace ChamberOrchestra\MenuBundle\Factory;

use ChamberOrchestra\MenuBundle\Factory\Extension\ExtensionInterface;
use ChamberOrchestra\MenuBundle\Menu\Item;
use ChamberOrchestra\MenuBundle\Menu\ItemInterface;

class Factory implements FactoryInterface
{
    private array $extensions = [];
    private ?array $sorted = null;

    public function createItem(string $name, array $options = [], bool $section = false): ItemInterface
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

    public function addExtensions(iterable $extensions): void
    {
        foreach ($extensions as $extension) {
            $this->addExtension($extension);
        }
    }

    private function getExtensions(): array
    {
        if (null === $this->sorted) {
            \krsort($this->extensions);
            $this->sorted = !empty($this->extensions) ? \array_merge(...$this->extensions) : [];
        }

        return $this->sorted;
    }
}
