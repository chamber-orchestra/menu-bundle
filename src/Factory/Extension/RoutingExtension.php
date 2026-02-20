<?php

declare(strict_types=1);

namespace ChamberOrchestra\MenuBundle\Factory\Extension;

use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class RoutingExtension implements ExtensionInterface
{
    public function __construct(private readonly UrlGeneratorInterface $generator)
    {
    }

    /**
     * @param array<string, mixed> $options
     *
     * @return array<string, mixed>
     */
    public function buildOptions(array $options = []): array
    {
        if (!isset($options['route'])) {
            return $options;
        }

        /** @var array<string, mixed> $params */
        $params = $options['route_params'] ?? [];
        /** @var int $referenceType */
        $referenceType = $options['route_type'] ?? UrlGeneratorInterface::ABSOLUTE_PATH;
        /** @var string $route */
        $route = $options['route'];

        $options['uri'] = $this->generator->generate($route, $params, $referenceType);
        /** @var array<int, array<string, mixed>> $existingRoutes */
        $existingRoutes = $options['routes'] ?? [];
        $options['routes'] = \array_merge_recursive(
            $existingRoutes,
            [
                [
                    'route' => $route,
                    'route_params' => $params,
                ],
            ]);

        return $options;
    }
}
