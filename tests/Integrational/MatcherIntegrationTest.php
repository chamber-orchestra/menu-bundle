<?php

declare(strict_types=1);

/*
 * This file is part of the ChamberOrchestra package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Tests\Integrational;

use ChamberOrchestra\MenuBundle\Factory\Factory;
use ChamberOrchestra\MenuBundle\Matcher\Matcher;
use ChamberOrchestra\MenuBundle\Matcher\Voter\RouteVoter;
use ChamberOrchestra\MenuBundle\Menu\MenuBuilder;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Tests Matcher + RouteVoter + real RequestStack working together.
 */
final class MatcherIntegrationTest extends TestCase
{
    private RequestStack $stack;
    private Matcher $matcher;
    private MenuBuilder $builder;

    protected function setUp(): void
    {
        $this->stack = new RequestStack();
        $this->matcher = new Matcher();
        $this->matcher->addVoters([new RouteVoter($this->stack)]);
        $this->builder = new MenuBuilder(new Factory());
    }

    #[Test]
    public function isCurrentReturnsTrueForActiveRoute(): void
    {
        $this->stack->push($this->makeRequest('app_home'));
        $this->builder->add('home', ['routes' => [['route' => 'app_home']]]);

        self::assertTrue($this->matcher->isCurrent($this->builder->build()->getFirstChild()));
    }

    #[Test]
    public function isCurrentReturnsFalseForDifferentRoute(): void
    {
        $this->stack->push($this->makeRequest('app_about'));
        $this->builder->add('home', ['routes' => [['route' => 'app_home']]]);

        self::assertFalse($this->matcher->isCurrent($this->builder->build()->getFirstChild()));
    }

    #[Test]
    public function isCurrentReturnsFalseWithNoRequest(): void
    {
        // No request pushed → RouteVoter returns null → isCurrent is false
        $this->builder->add('home', ['routes' => [['route' => 'app_home']]]);

        self::assertFalse($this->matcher->isCurrent($this->builder->build()->getFirstChild()));
    }

    #[Test]
    public function isAncestorReturnsTrueForParentOfCurrentRoute(): void
    {
        $this->stack->push($this->makeRequest('app_blog_post'));

        $this->builder
            ->add('blog', ['routes' => [['route' => 'app_blog']]])
            ->children()
                ->add('post', ['routes' => [['route' => 'app_blog_post']]])
            ->end();

        $root = $this->builder->build();
        $blog = $root->getFirstChild();

        self::assertFalse($this->matcher->isCurrent($blog));
        self::assertTrue($this->matcher->isAncestor($blog));
    }

    #[Test]
    public function clearResetsCacheAllowingRematch(): void
    {
        $this->stack->push($this->makeRequest('app_home'));
        $this->builder->add('home', ['routes' => [['route' => 'app_home']]]);
        $home = $this->builder->build()->getFirstChild();

        self::assertTrue($this->matcher->isCurrent($home));

        $this->matcher->clear();
        $this->stack->pop();

        // No request now → false
        self::assertFalse($this->matcher->isCurrent($home));
    }

    #[Test]
    public function regexRoutePatternMatchesMultipleActualRoutes(): void
    {
        $this->stack->push($this->makeRequest('app_blog_category_list'));
        $this->builder->add('blog', ['routes' => [['route' => 'app_blog_.+']]]);

        self::assertTrue($this->matcher->isCurrent($this->builder->build()->getFirstChild()));
    }

    #[Test]
    public function routeParamsMustMatchForPositiveResult(): void
    {
        $request = $this->makeRequest('app_post', ['_route_params' => ['id' => '5']]);
        $this->stack->push($request);

        $this->builder
            ->add('post_5', ['routes' => [['route' => 'app_post', 'route_params' => ['id' => '5']]]])
            ->add('post_9', ['routes' => [['route' => 'app_post', 'route_params' => ['id' => '9']]]]);

        $root = $this->builder->build();

        self::assertTrue($this->matcher->isCurrent($root->getFirstChild()));
        self::assertFalse($this->matcher->isCurrent($root->getLastChild()));
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
