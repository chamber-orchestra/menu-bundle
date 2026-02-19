<?php

declare(strict_types=1);

namespace Tests\Unit\Matcher\Voter;

use ChamberOrchestra\MenuBundle\Exception\InvalidArgumentException;
use ChamberOrchestra\MenuBundle\Matcher\Voter\RouteVoter;
use ChamberOrchestra\MenuBundle\Menu\Item;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

final class RouteVoterTest extends TestCase
{
    private RequestStack $stack;
    private RouteVoter $voter;

    protected function setUp(): void
    {
        $this->stack = new RequestStack();
        $this->voter = new RouteVoter($this->stack);
    }

    #[Test]
    public function matchItemReturnsNullWhenNoRequest(): void
    {
        $item = new Item('home', ['routes' => [['route' => 'app_home']]]);

        self::assertNull($this->voter->matchItem($item));
    }

    #[Test]
    public function matchItemReturnsNullWhenRequestHasNoRoute(): void
    {
        $this->stack->push(new Request());

        self::assertNull($this->voter->matchItem(new Item('home', ['routes' => [['route' => 'app_home']]])));
    }

    #[Test]
    public function matchItemReturnsNullWhenItemHasNoRoutes(): void
    {
        $this->stack->push($this->makeRequest('app_home'));

        self::assertNull($this->voter->matchItem(new Item('home')));
    }

    #[Test]
    public function matchItemReturnsNullWhenItemHasEmptyRoutes(): void
    {
        $this->stack->push($this->makeRequest('app_home'));

        self::assertNull($this->voter->matchItem(new Item('home', ['routes' => []])));
    }

    #[Test]
    public function matchItemReturnsTrueOnExactRouteMatch(): void
    {
        $this->stack->push($this->makeRequest('app_home'));
        $item = new Item('home', ['routes' => [['route' => 'app_home']]]);

        self::assertTrue($this->voter->matchItem($item));
    }

    #[Test]
    public function matchItemReturnsNullWhenRouteDoesNotMatch(): void
    {
        $this->stack->push($this->makeRequest('app_about'));
        $item = new Item('home', ['routes' => [['route' => 'app_home']]]);

        self::assertNull($this->voter->matchItem($item));
    }

    #[Test]
    public function matchItemSupportsStringShorthand(): void
    {
        $this->stack->push($this->makeRequest('app_home'));
        $item = new Item('home', ['routes' => ['app_home']]);

        self::assertTrue($this->voter->matchItem($item));
    }

    #[Test]
    public function matchItemSupportsRegexPattern(): void
    {
        $this->stack->push($this->makeRequest('app_blog_post'));
        $item = new Item('blog', ['routes' => [['route' => 'app_blog_.+']]]);

        self::assertTrue($this->voter->matchItem($item));
    }

    #[Test]
    public function matchItemRegexDoesNotMatchPartially(): void
    {
        $this->stack->push($this->makeRequest('app_blog_post_extra'));
        // Anchors ^…$ make this exact: 'app_blog' won't match 'app_blog_post_extra'
        $item = new Item('blog', ['routes' => [['route' => 'app_blog']]]);

        self::assertNull($this->voter->matchItem($item));
    }

    #[Test]
    public function matchItemChecksRouteParamsForMatch(): void
    {
        $request = $this->makeRequest('app_post', ['_route_params' => ['id' => '42']]);
        $this->stack->push($request);

        $item = new Item('post', ['routes' => [['route' => 'app_post', 'route_params' => ['id' => '42']]]]);

        self::assertTrue($this->voter->matchItem($item));
    }

    #[Test]
    public function matchItemReturnsNullWhenRouteParamsDiffer(): void
    {
        $request = $this->makeRequest('app_post', ['_route_params' => ['id' => '42']]);
        $this->stack->push($request);

        $item = new Item('post', ['routes' => [['route' => 'app_post', 'route_params' => ['id' => '99']]]]);

        self::assertNull($this->voter->matchItem($item));
    }

    #[Test]
    public function matchItemReturnsTrueOnFirstMatchingRouteInList(): void
    {
        $this->stack->push($this->makeRequest('app_about'));
        $item = new Item('nav', ['routes' => [['route' => 'app_home'], ['route' => 'app_about']]]);

        self::assertTrue($this->voter->matchItem($item));
    }

    #[Test]
    public function matchItemThrowsForNonStringNonArrayRouteEntry(): void
    {
        $this->stack->push($this->makeRequest('app_home'));
        $item = new Item('home', ['routes' => [42]]);

        $this->expectException(InvalidArgumentException::class);
        $this->voter->matchItem($item);
    }

    #[Test]
    public function matchItemThrowsForArrayRouteEntryMissingRouteKey(): void
    {
        $this->stack->push($this->makeRequest('app_home'));
        $item = new Item('home', ['routes' => [['route_params' => ['id' => 1]]]]);

        $this->expectException(InvalidArgumentException::class);
        $this->voter->matchItem($item);
    }

    private function makeRequest(string $route, array $extraAttributes = []): Request
    {
        $request = new Request();
        $request->attributes->set('_route', $route);
        foreach ($extraAttributes as $key => $value) {
            $request->attributes->set($key, $value);
        }

        return $request;
    }
}
