<?php

declare(strict_types=1);

/*
 * This file is part of the ChamberOrchestra package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Tests\Unit\Factory\Extension;

use ChamberOrchestra\MenuBundle\Factory\Extension\RoutingExtension;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

#[AllowMockObjectsWithoutExpectations]
final class RoutingExtensionTest extends TestCase
{
    private UrlGeneratorInterface $generator;
    private RoutingExtension $ext;

    protected function setUp(): void
    {
        $this->generator = $this->createMock(UrlGeneratorInterface::class);
        $this->ext = new RoutingExtension($this->generator);
    }

    #[Test]
    public function returnsOptionsUnchangedWhenNoRouteKey(): void
    {
        $this->generator->expects(self::never())->method('generate');

        $options = ['label' => 'Home'];
        self::assertSame($options, $this->ext->buildOptions($options));
    }

    #[Test]
    public function generatesAbsolutePathByDefault(): void
    {
        $this->generator
            ->expects(self::once())
            ->method('generate')
            ->with('app_home', [], UrlGeneratorInterface::ABSOLUTE_PATH)
            ->willReturn('/');

        $result = $this->ext->buildOptions(['route' => 'app_home']);

        self::assertSame('/', $result['uri']);
    }

    #[Test]
    public function generatesUriWithRouteParams(): void
    {
        $this->generator
            ->expects(self::once())
            ->method('generate')
            ->with('app_post', ['id' => 1], UrlGeneratorInterface::ABSOLUTE_PATH)
            ->willReturn('/posts/1');

        $result = $this->ext->buildOptions(['route' => 'app_post', 'route_params' => ['id' => 1]]);

        self::assertSame('/posts/1', $result['uri']);
    }

    #[Test]
    public function honorsCustomRouteType(): void
    {
        $this->generator
            ->expects(self::once())
            ->method('generate')
            ->with('app_home', [], UrlGeneratorInterface::ABSOLUTE_URL)
            ->willReturn('https://example.com/');

        $this->ext->buildOptions(['route' => 'app_home', 'route_type' => UrlGeneratorInterface::ABSOLUTE_URL]);
    }

    #[Test]
    public function appendsCurrentRouteToRoutesArray(): void
    {
        $this->generator->method('generate')->willReturn('/');

        $result = $this->ext->buildOptions(['route' => 'app_home']);

        self::assertCount(1, $result['routes']);
        self::assertSame('app_home', $result['routes'][0]['route']);
        self::assertSame([], $result['routes'][0]['route_params']);
    }

    #[Test]
    public function mergesWithExistingRoutesArray(): void
    {
        $this->generator->method('generate')->willReturn('/');

        $existing = [['route' => 'app_home_redirect']];
        $result = $this->ext->buildOptions([
            'route' => 'app_home',
            'routes' => $existing,
        ]);

        self::assertCount(2, $result['routes']);
    }

    #[Test]
    public function routeParamsAreStoredInRoutesEntry(): void
    {
        $this->generator->method('generate')->willReturn('/posts/42');

        $result = $this->ext->buildOptions(['route' => 'app_post', 'route_params' => ['id' => 42]]);

        self::assertSame(['id' => 42], $result['routes'][0]['route_params']);
    }
}
