<?php

declare(strict_types=1);

namespace ChamberOrchestra\MenuBundle\Twig\Helper;

use ChamberOrchestra\MenuBundle\Navigation\NavigationInterface;
use ChamberOrchestra\MenuBundle\NavigationFactory;
use ChamberOrchestra\MenuBundle\Renderer\TwigRenderer;

class Helper
{
    public function __construct(
        private readonly TwigRenderer $renderer,
        private readonly NavigationFactory $factory,
    ) {
    }

    /**
     * @param array<string, mixed> $options
     */
    public function render(NavigationInterface|string $menu, string $template, array $options = []): string
    {
        return $this->renderer->render($this->factory->create($menu, []), $template, $options);
    }
}
