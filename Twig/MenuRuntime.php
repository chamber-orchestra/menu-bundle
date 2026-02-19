<?php

declare(strict_types=1);

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
     * @throws \Psr\Cache\InvalidArgumentException
     */
    public function render(NavigationInterface|string $menu, string $template, array $options = []): string
    {
        return $this->helper->render($menu, $template, $options);
    }
}
