<?php declare(strict_types=1);

namespace ChamberOrchestra\MenuBundle\Accessor;

use ChamberOrchestra\MenuBundle\Menu\ItemInterface;
use Doctrine\Common\Collections\Collection;

interface AccessorInterface
{
    public function hasAccess(ItemInterface $item): bool;

    public function hasAccessToChildren(Collection $items): bool;
}