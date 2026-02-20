<?php

declare(strict_types=1);

namespace ChamberOrchestra\MenuBundle\Twig;

use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class MenuExtension extends AbstractExtension
{
    public function getFunctions(): array
    {
        return [
            new TwigFunction('render_menu', [MenuRuntime::class, 'render'], ['is_safe' => ['html']]),
        ];
    }
}
