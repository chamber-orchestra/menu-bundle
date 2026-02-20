<?php

declare(strict_types=1);

/*
 * This file is part of the ChamberOrchestra package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ChamberOrchestra\MenuBundle\Twig;

use ChamberOrchestra\MenuBundle\Navigation\NavigationInterface;
use ChamberOrchestra\MenuBundle\Twig\Helper\Helper;
use Twig\Extension\RuntimeExtensionInterface;

class MenuRuntime implements RuntimeExtensionInterface
{
    public function __construct(private readonly Helper $helper)
    {
    }

    /**
     * @param array<string, mixed> $options
     *
     * @throws \Psr\Cache\InvalidArgumentException
     */
    public function render(NavigationInterface|string $menu, string $template, array $options = []): string
    {
        return $this->helper->render($menu, $template, $options);
    }
}
