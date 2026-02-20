<?php

declare(strict_types=1);

/*
 * This file is part of the ChamberOrchestra package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ChamberOrchestra\MenuBundle\Renderer;

use ChamberOrchestra\MenuBundle\Accessor\Accessor;
use ChamberOrchestra\MenuBundle\Matcher\Matcher;
use ChamberOrchestra\MenuBundle\Menu\Item;
use Twig\Environment;

class TwigRenderer
{
    public function __construct(
        private readonly Environment $environment,
        private readonly Matcher $matcher,
        private readonly Accessor $accessor
    ) {
    }

    /**
     * @param array<string, mixed> $options
     *
     * @throws \Twig\Error\LoaderError
     * @throws \Twig\Error\RuntimeError
     * @throws \Twig\Error\SyntaxError
     */
    public function render(Item $item, string $template, array $options = []): string
    {
        return $this->environment->render($template, \array_merge($options, [
            'root' => $item,
            'matcher' => $this->matcher,
            'accessor' => $this->accessor,
        ]));
    }
}
