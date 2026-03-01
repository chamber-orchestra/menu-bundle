<?php

declare(strict_types=1);

/*
 * This file is part of the ChamberOrchestra package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ChamberOrchestra\MenuBundle\Twig;

use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class MenuExtension extends AbstractExtension
{
    public function getFunctions(): array
    {
        return [
            new TwigFunction('render_menu', [MenuRuntime::class, 'render'], ['is_safe' => ['html']]),
            new TwigFunction('menu_get', [MenuRuntime::class, 'get']),
            new TwigFunction('menu_breadcrumbs', [MenuRuntime::class, 'breadcrumbs']),
        ];
    }
}
