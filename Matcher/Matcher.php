<?php

declare(strict_types=1);

namespace ChamberOrchestra\MenuBundle\Matcher;

use ChamberOrchestra\MenuBundle\Menu\ItemInterface;

/**
 * A MatcherInterface implementation using a voter system.
 */
class Matcher implements MatcherInterface
{
    private \SplObjectStorage $cache;
    private iterable $voters = [];

    public function __construct()
    {
        $this->cache = new \SplObjectStorage();
    }

    public function addVoters(iterable $voters): void
    {
        $this->voters = $voters;
    }

    public function isCurrent(ItemInterface $item): bool
    {
        if (isset($this->cache[$item])) {
            return (bool) $this->cache[$item];
        }

        $current = false;
        foreach ($this->voters as $voter) {
            $result = $voter->matchItem($item);
            if (null !== $result) {
                $current = $result;
                break;
            }
        }

        $this->cache[$item] = $current;

        return $current;
    }

    public function isAncestor(ItemInterface $item, ?int $depth = null): bool
    {
        if (0 === $depth) {
            return false;
        }

        $childDepth = null === $depth ? null : $depth - 1;
        foreach ($item->getChildren() as $child) {
            if ($this->isCurrent($child) || $this->isAncestor($child, $childDepth)) {
                return true;
            }
        }

        return false;
    }

    public function clear(): void
    {
        $this->cache->removeAll($this->cache);
    }
}
