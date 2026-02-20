<?php

declare(strict_types=1);

/*
 * This file is part of the ChamberOrchestra package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Tests\Unit\Navigation;

use ChamberOrchestra\MenuBundle\Menu\MenuBuilder;
use ChamberOrchestra\MenuBundle\Navigation\ClosureNavigation;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class ClosureNavigationTest extends TestCase
{
    #[Test]
    public function buildInvokesClosureWithBuilderAndOptions(): void
    {
        $called = false;
        $receivedBuilder = null;
        $receivedOptions = null;

        $nav = new ClosureNavigation(
            function (MenuBuilder $builder, array $options) use (&$called, &$receivedBuilder, &$receivedOptions): void {
                $called = true;
                $receivedBuilder = $builder;
                $receivedOptions = $options;
            }
        );

        $builder = $this->createStub(MenuBuilder::class);
        $nav->build($builder, ['locale' => 'ru']);

        self::assertTrue($called);
        self::assertSame($builder, $receivedBuilder);
        self::assertSame(['locale' => 'ru'], $receivedOptions);
    }

    #[Test]
    public function buildPassesEmptyOptionsWhenNoneGiven(): void
    {
        $received = null;
        $nav = new ClosureNavigation(
            function (MenuBuilder $b, array $options) use (&$received): void {
                $received = $options;
            }
        );

        $nav->build($this->createStub(MenuBuilder::class));

        self::assertSame([], $received);
    }
}
