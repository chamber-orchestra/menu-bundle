<?php

declare(strict_types=1);

/*
 * This file is part of the ChamberOrchestra package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ChamberOrchestra\MenuBundle\Registry;

use ChamberOrchestra\MenuBundle\Exception\InvalidArgumentException;
use ChamberOrchestra\MenuBundle\Navigation\NavigationInterface;
use Symfony\Component\DependencyInjection\Attribute\AutowireLocator;
use Symfony\Component\DependencyInjection\ServiceLocator;

class NavigationRegistry
{
    /**
     * @param ServiceLocator<NavigationInterface> $locator
     */
    public function __construct(
        #[AutowireLocator('chamber_orchestra_menu.navigation')]
        private readonly ServiceLocator $locator
    ) {
    }

    public function get(string $id): NavigationInterface
    {
        if (!$this->locator->has($id)) {
            throw new InvalidArgumentException(\sprintf('The menu "%s" is not defined.', $id));
        }

        return $this->locator->get($id);
    }
}
