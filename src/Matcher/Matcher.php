<?php

declare(strict_types=1);

/*
 * This file is part of the ChamberOrchestra package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ChamberOrchestra\MenuBundle\Matcher;

use ChamberOrchestra\MenuBundle\Matcher\Voter\VoterInterface;
use ChamberOrchestra\MenuBundle\Menu\Item;

class Matcher
{
    /** @var \SplObjectStorage<Item, bool> */
    private \SplObjectStorage $cache;
    /** @var iterable<VoterInterface> */
    private iterable $voters = [];

    public function __construct()
    {
        $this->cache = new \SplObjectStorage();
    }

    /**
     * @param iterable<VoterInterface> $voters
     */
    public function addVoters(iterable $voters): void
    {
        $this->voters = $voters;
    }

    public function isCurrent(Item $item): bool
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

    public function isAncestor(Item $item, ?int $depth = null): bool
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
