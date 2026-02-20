<?php

declare(strict_types=1);

namespace ChamberOrchestra\MenuBundle\Accessor;

use ChamberOrchestra\MenuBundle\Menu\Item;
use Doctrine\Common\Collections\Collection;
use Ds\Map;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

class Accessor
{
    /** @var array<string, bool> */
    private array $grants = [];
    /** @var Map<Item, bool> */
    private Map $map;

    public function __construct(private readonly AuthorizationCheckerInterface $authorizationChecker)
    {
        $this->map = new Map();
    }

    public function hasAccess(Item $item): bool
    {
        return $this->hasAccessToItem($item);
    }

    /**
     * @param Collection<int, Item> $items
     */
    public function hasAccessToChildren(Collection $items): bool
    {
        foreach ($items as $item) {
            if ($this->hasAccessToItem($item)) {
                return true;
            }
        }

        return false;
    }

    private function hasAccessToItem(Item $item): bool
    {
        if ($this->map->hasKey($item)) {
            return (bool) $this->map->get($item);
        }

        $isGranted = $this->isGranted($item);
        $this->map->put($item, $isGranted);

        return $isGranted;
    }

    private function isGranted(Item $item): bool
    {
        foreach ($item->getRoles() as $role) {
            if (isset($this->grants[$role])) {
                if (!$this->grants[$role]) {
                    return false;
                }

                continue;
            }

            if (!($this->grants[$role] = $isGranted = $this->authorizationChecker->isGranted($role))) {
                return false;
            }
        }

        return true;
    }
}
