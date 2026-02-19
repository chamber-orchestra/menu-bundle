<?php declare(strict_types=1);

namespace ChamberOrchestra\MenuBundle\Accessor;

use ChamberOrchestra\MenuBundle\Menu\ItemInterface;
use Doctrine\Common\Collections\Collection;
use Ds\Map;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

class Accessor implements AccessorInterface
{
    private array $grants = [];
    private Map $map;

    public function __construct(private readonly AuthorizationCheckerInterface $authorizationChecker)
    {
        $this->map = new Map();
    }

    public function hasAccess(ItemInterface $item): bool
    {
        return $this->hasAccessToItem($item);
    }

    public function hasAccessToChildren(Collection $items): bool
    {
        foreach ($items as $item) {
            if ($this->hasAccessToItem($item)) {
                return true;
            }
        }

        return false;
    }

    private function hasAccessToItem(ItemInterface $item): bool
    {
        if ($this->map->hasKey($item)) {
            return $this->map->get($item);
        }

        $isGranted = $this->isGranted($item);
        $this->map->put($item, $isGranted);

        return $isGranted;
    }

    private function isGranted(ItemInterface $item): bool
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