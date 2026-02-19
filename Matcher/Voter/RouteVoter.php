<?php declare(strict_types=1);

namespace ChamberOrchestra\MenuBundle\Matcher\Voter;

use ChamberOrchestra\MenuBundle\Exception\InvalidArgumentException;
use ChamberOrchestra\MenuBundle\Menu\ItemInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

class RouteVoter implements VoterInterface
{
    private ?Request $lastRequest = null;
    private ?string $lastRoute = null;
    private array $lastRouteParams = [];

    public function __construct(private readonly RequestStack $stack)
    {
    }

    public function matchItem(ItemInterface $item): ?bool
    {
        $request = $this->stack->getCurrentRequest();
        if (null === $request) {
            return null;
        }

        if ($this->lastRequest !== $request) {
            $this->lastRequest = $request;
            $this->lastRoute = $request->attributes->get('_route');
            $this->lastRouteParams = $request->attributes->get('_route_params', []);
        }

        if (null === $this->lastRoute) {
            return null;
        }

        $routes = (array) $item->getOption('routes', []);

        foreach ($routes as $testedRoute) {
            if (\is_string($testedRoute)) {
                $testedRoute = ['route' => $testedRoute];
            }

            if (!\is_array($testedRoute)) {
                throw new InvalidArgumentException('Routes extra items must be strings or arrays.');
            }

            if ($this->isMatchingRoute($this->lastRoute, $this->lastRouteParams, $testedRoute)) {
                return true;
            }
        }

        return null;
    }

    private function isMatchingRoute(string $route, array $routeParams, array $testedRoute): bool
    {
        if (!isset($testedRoute['route'])) {
            throw new InvalidArgumentException('Routes extra items must have a "route" or "pattern" key.');
        }

        $pattern = '/^'.$testedRoute['route'].'$/';
        if (!\preg_match($pattern, $route)) {
            return false;
        }

        if (!isset($testedRoute['route_params'])) {
            return true;
        }

        foreach ($testedRoute['route_params'] as $name => $value) {
            if (!isset($routeParams[$name]) || $routeParams[$name] !== (string) $value) {
                return false;
            }
        }

        return true;
    }
}
