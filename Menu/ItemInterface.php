<?php

declare(strict_types=1);

namespace ChamberOrchestra\MenuBundle\Menu;

use Doctrine\Common\Collections\Collection;

interface ItemInterface extends \Countable, \IteratorAggregate
{
    public function getName(): string;

    public function getLabel(): string;

    public function getUri(): ?string;

    public function getRoles(): array;

    public function getOption(string $name, mixed $default = null): mixed;

    public function add(ItemInterface $item, bool $prepend = false): ItemInterface;

    public function getChildren(): Collection;

    public function getFirstChild(): ?ItemInterface;

    public function getLastChild(): ?ItemInterface;

    public function isSection(): bool;
}
