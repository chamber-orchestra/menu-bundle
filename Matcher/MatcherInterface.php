<?php

declare(strict_types=1);

namespace ChamberOrchestra\MenuBundle\Matcher;

use ChamberOrchestra\MenuBundle\Menu\ItemInterface;

/**
 * Interface implemented by the item matcher.
 */
interface MatcherInterface
{
    /**
     * Checks whether an item is current.
     */
    public function isCurrent(ItemInterface $item): bool;

    /**
     * Checks whether an item is the ancestor of a current item.
     */
    public function isAncestor(ItemInterface $item, ?int $depth = null): bool;

    /**
     * Clears the state of the matcher.
     */
    public function clear(): void;
}
