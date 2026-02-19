<?php declare(strict_types=1);

namespace ChamberOrchestra\MenuBundle\Renderer;

use ChamberOrchestra\MenuBundle\Accessor\AccessorInterface;
use ChamberOrchestra\MenuBundle\Matcher\MatcherInterface;
use ChamberOrchestra\MenuBundle\Menu\ItemInterface;
use Twig\Environment;

class TwigRenderer implements RendererInterface
{
    public function __construct(
        private readonly Environment $environment,
        private readonly MatcherInterface $matcher,
        private readonly AccessorInterface $accessor
    )
    {
    }

    /**
     * @throws \Twig\Error\LoaderError
     * @throws \Twig\Error\RuntimeError
     * @throws \Twig\Error\SyntaxError
     */
    public function render(ItemInterface $item, string $template, array $options = []): string
    {
        return $this->environment->render($template, \array_merge($options, [
            'root' => $item,
            'matcher' => $this->matcher,
            'accessor' => $this->accessor,
        ]));
    }
}
