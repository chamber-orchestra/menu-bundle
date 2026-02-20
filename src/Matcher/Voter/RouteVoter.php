<?php

declare(strict_types=1);

namespace ChamberOrchestra\MenuBundle\Matcher\Voter;

use ChamberOrchestra\MenuBundle\Exception\InvalidArgumentException;
use ChamberOrchestra\MenuBundle\Menu\Item;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

class RouteVoter
{
    private ?Request $lastRequest = null;
    private ?string $lastRoute = null;
    /** @var array<string, mixed> */
    private array $lastRouteParams = [];

    public function __construct(private readonly RequestStack $stack)
    {
    }

    public function matchItem(Item $item): ?bool
    {
        $request = $this->stack->getCurrentRequest();
        if (null === $request) {
            return null;
        }

        if ($this->lastRequest !== $request) {
            $this->lastRequest = $request;
            /** @var string|null $route */
            $route = $request->attributes->get('_route');
            $this->lastRoute = $route;
            /** @var array<string, mixed> $routeParams */
            $routeParams = $request->attributes->get('_route_params', []);
            $this->lastRouteParams = $routeParams;
        }

        if (null === $this->lastRoute) {
            return null;
        }

        /** @var list<mixed> $routes */
        $routes = (array) $item->getOption('routes', []);

        foreach ($routes as $testedRoute) {
            if (\is_string($testedRoute)) {
                $testedRoute = ['route' => $testedRoute];
            }

            if (!\is_array($testedRoute)) {
                throw new InvalidArgumentException('Routes extra items must be strings or arrays.');
            }

            /** @var array<string, mixed> $testedRoute */
            if ($this->isMatchingRoute($this->lastRoute, $this->lastRouteParams, $testedRoute)) {
                return true;
            }
        }

        return null;
    }

    /**
     * @param array<string, mixed> $routeParams
     * @param array<string, mixed> $testedRoute
     */
    private function isMatchingRoute(string $route, array $routeParams, array $testedRoute): bool
    {
        if (!isset($testedRoute['route'])) {
            throw new InvalidArgumentException('Routes extra items must have a "route" or "pattern" key.');
        }

        /** @var string $routePattern */
        $routePattern = $testedRoute['route'];
        $pattern = '/^'.$routePattern.'$/';
        if (!\preg_match($pattern, $route)) {
            return false;
        }

        if (!isset($testedRoute['route_params'])) {
            return true;
        }

        /** @var array<string, string> $testedParams */
        $testedParams = $testedRoute['route_params'];
        foreach ($testedParams as $name => $value) {
            /** @var string $paramValue */
            $paramValue = $routeParams[$name] ?? null;
            if (!isset($routeParams[$name]) || $paramValue !== $value) {
                return false;
            }
        }

        return true;
    }
}
