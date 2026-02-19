<?php

declare(strict_types=1);

namespace ChamberOrchestra\MenuBundle\Renderer;

use ChamberOrchestra\MenuBundle\Menu\ItemInterface;

interface RendererInterface
{
    /**
     * Renders menu tree.
     */
    public function render(ItemInterface $item, string $template, array $options = []): string;
}
