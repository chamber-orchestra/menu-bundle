<?php

declare(strict_types=1);

namespace ChamberOrchestra\MenuBundle\Factory\Extension;

use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class RoutingExtension implements ExtensionInterface
{
    public function __construct(private readonly UrlGeneratorInterface $generator)
    {
    }

    public function buildOptions(array $options = []): array
    {
        if (!isset($options['route'])) {
            return $options;
        }

        $params = $options['route_params'] ?? [];
        $referenceType = $options['route_type'] ?? UrlGeneratorInterface::ABSOLUTE_PATH;

        $options['uri'] = $this->generator->generate($options['route'], $params, $referenceType);
        $options['routes'] = \array_merge_recursive(
            $options['routes'] ?? [],
            [
                [
                    'route' => $options['route'],
                    'route_params' => $params,
                ],
            ]);

        return $options;
    }
}
