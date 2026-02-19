<?php

declare(strict_types=1);

namespace ChamberOrchestra\MenuBundle\Matcher\Voter;

use ChamberOrchestra\MenuBundle\Menu\ItemInterface;

/**
 * Interface implemented by the matching voters.
 */
interface VoterInterface
{
    /**
     * Checks whether an item is current.
     *
     * If the voter is not able to determine a result,
     * it should return null to let other voters do the job.
     *
     * @param ItemInterface $item
     *
     * @return bool|null
     */
    public function matchItem(ItemInterface $item): ?bool;
}
