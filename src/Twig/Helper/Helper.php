<?php

declare(strict_types=1);

/*
 * This file is part of the ChamberOrchestra package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ChamberOrchestra\MenuBundle\Twig\Helper;

use ChamberOrchestra\MenuBundle\Matcher\Matcher;
use ChamberOrchestra\MenuBundle\Menu\Item;
use ChamberOrchestra\MenuBundle\Navigation\NavigationInterface;
use ChamberOrchestra\MenuBundle\NavigationFactory;
use ChamberOrchestra\MenuBundle\Renderer\TwigRenderer;

class Helper
{
    public function __construct(
        private readonly TwigRenderer $renderer,
        private readonly NavigationFactory $factory,
        private readonly Matcher $matcher,
        private readonly ?string $defaultTemplate = null,
    ) {
    }

    /**
     * @param array<string, mixed> $options
     */
    public function render(NavigationInterface|string $menu, ?string $template = null, array $options = []): string
    {
        $template ??= $this->defaultTemplate;

        if (null === $template) {
            throw new \InvalidArgumentException('No template provided and no default template configured. Pass a template argument or set "chamber_orchestra_menu.default_template".');
        }

        return $this->renderer->render($this->factory->create($menu, []), $template, $options);
    }

    public function get(NavigationInterface|string $menu): Item
    {
        return $this->factory->create($menu, []);
    }

    /**
     * @return list<Item>
     */
    public function breadcrumbs(NavigationInterface|string $menu): array
    {
        $root = $this->factory->create($menu, []);

        return $this->findCurrentPath($root);
    }

    /**
     * @return list<Item>
     */
    private function findCurrentPath(Item $item): array
    {
        foreach ($item->getChildren() as $child) {
            if ($this->matcher->isCurrent($child)) {
                return [$child];
            }

            $path = $this->findCurrentPath($child);
            if ([] !== $path) {
                return [$child, ...$path];
            }
        }

        return [];
    }
}
