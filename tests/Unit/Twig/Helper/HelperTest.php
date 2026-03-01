<?php

declare(strict_types=1);

/*
 * This file is part of the ChamberOrchestra package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Tests\Unit\Twig\Helper;

use ChamberOrchestra\MenuBundle\Matcher\Matcher;
use ChamberOrchestra\MenuBundle\Matcher\Voter\VoterInterface;
use ChamberOrchestra\MenuBundle\Menu\Item;
use ChamberOrchestra\MenuBundle\NavigationFactory;
use ChamberOrchestra\MenuBundle\Renderer\TwigRenderer;
use ChamberOrchestra\MenuBundle\Twig\Helper\Helper;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class HelperTest extends TestCase
{
    private Matcher $matcher;

    protected function setUp(): void
    {
        $this->matcher = new Matcher();
    }

    #[Test]
    public function breadcrumbsReturnsEmptyWhenNoCurrentItem(): void
    {
        $root = new Item('root');
        $root->add(new Item('scores'));
        $root->add(new Item('rehearsals'));

        $this->matcher->addVoters([$this->makeVoter(null)]);

        $helper = $this->createHelper($root);

        self::assertSame([], $helper->breadcrumbs('App\\Nav'));
    }

    #[Test]
    public function breadcrumbsReturnsPathToNestedCurrentItem(): void
    {
        $grandchild = new Item('sonata');
        $child = new Item('compositions');
        $child->add($grandchild);
        $root = new Item('root');
        $root->add($child);

        $voter = $this->createStub(VoterInterface::class);
        $voter->method('matchItem')
            ->willReturnCallback(static fn (Item $item) => $item === $grandchild ? true : null);
        $this->matcher->addVoters([$voter]);

        $helper = $this->createHelper($root);
        $path = $helper->breadcrumbs('App\\Nav');

        self::assertCount(2, $path);
        self::assertSame('compositions', $path[0]->getName());
        self::assertSame('sonata', $path[1]->getName());
    }

    #[Test]
    public function breadcrumbsExcludesRoot(): void
    {
        $child = new Item('scores');
        $root = new Item('root');
        $root->add($child);

        $voter = $this->createStub(VoterInterface::class);
        $voter->method('matchItem')
            ->willReturnCallback(static fn (Item $item) => $item === $child ? true : null);
        $this->matcher->addVoters([$voter]);

        $helper = $this->createHelper($root);
        $path = $helper->breadcrumbs('App\\Nav');

        self::assertCount(1, $path);
        self::assertSame('scores', $path[0]->getName());
    }

    #[Test]
    public function breadcrumbsReturnsDeeplyNestedPath(): void
    {
        $level3 = new Item('movement_iii');
        $level2 = new Item('symphony');
        $level2->add($level3);
        $level1 = new Item('compositions');
        $level1->add($level2);
        $root = new Item('root');
        $root->add($level1);

        $voter = $this->createStub(VoterInterface::class);
        $voter->method('matchItem')
            ->willReturnCallback(static fn (Item $item) => $item === $level3 ? true : null);
        $this->matcher->addVoters([$voter]);

        $helper = $this->createHelper($root);
        $path = $helper->breadcrumbs('App\\Nav');

        self::assertCount(3, $path);
        self::assertSame('compositions', $path[0]->getName());
        self::assertSame('symphony', $path[1]->getName());
        self::assertSame('movement_iii', $path[2]->getName());
    }

    private function createHelper(Item $root): Helper
    {
        $factory = $this->createStub(NavigationFactory::class);
        $factory->method('create')->willReturn($root);

        $renderer = $this->createStub(TwigRenderer::class);

        return new Helper($renderer, $factory, $this->matcher);
    }

    private function makeVoter(?bool $result): VoterInterface
    {
        $voter = $this->createStub(VoterInterface::class);
        $voter->method('matchItem')->willReturn($result);

        return $voter;
    }
}
