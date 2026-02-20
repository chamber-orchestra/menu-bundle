<?php

declare(strict_types=1);

/*
 * This file is part of the ChamberOrchestra package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Tests\Unit\Menu;

use ChamberOrchestra\MenuBundle\Exception\LogicException;
use ChamberOrchestra\MenuBundle\Factory\Factory;
use ChamberOrchestra\MenuBundle\Menu\MenuBuilder;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class MenuBuilderTest extends TestCase
{
    private MenuBuilder $builder;

    protected function setUp(): void
    {
        $this->builder = new MenuBuilder(new Factory());
    }

    #[Test]
    public function buildReturnsRootItem(): void
    {
        $root = $this->builder->build();

        self::assertSame('root', $root->getName());
        self::assertCount(0, $root);
    }

    #[Test]
    public function addCreatesChildOnRoot(): void
    {
        $this->builder->add('home');

        $root = $this->builder->build();

        self::assertCount(1, $root);
        self::assertSame('home', $root->getFirstChild()->getName());
    }

    #[Test]
    public function addReturnsSelf(): void
    {
        self::assertSame($this->builder, $this->builder->add('home'));
    }

    #[Test]
    public function addPassesOptions(): void
    {
        $this->builder->add('home', ['label' => 'Home', 'uri' => '/']);

        $item = $this->builder->build()->getFirstChild();

        self::assertSame('Home', $item->getLabel());
        self::assertSame('/', $item->getUri());
    }

    #[Test]
    public function addMultipleItemsInOrder(): void
    {
        $this->builder->add('a')->add('b')->add('c');

        $root = $this->builder->build();

        self::assertCount(3, $root);
        self::assertSame('a', $root->getFirstChild()->getName());
        self::assertSame('c', $root->getLastChild()->getName());
    }

    #[Test]
    public function addWithPrependInsertsAtFront(): void
    {
        $this->builder->add('first');
        $this->builder->add('prepended', prepend: true);

        $root = $this->builder->build();

        self::assertSame('prepended', $root->getFirstChild()->getName());
        self::assertSame('first', $root->getLastChild()->getName());
    }

    #[Test]
    public function addWithSectionFlagCreatesSection(): void
    {
        $this->builder->add('section', section: true);

        self::assertTrue($this->builder->build()->getFirstChild()->isSection());
    }

    #[Test]
    public function childrenDescendsIntoLastAddedItem(): void
    {
        $this->builder
            ->add('parent')
            ->children()
                ->add('child')
            ->end();

        $parent = $this->builder->build()->getFirstChild();

        self::assertCount(1, $parent);
        self::assertSame('child', $parent->getFirstChild()->getName());
    }

    #[Test]
    public function childrenReturnsSelf(): void
    {
        $this->builder->add('parent');

        self::assertSame($this->builder, $this->builder->children());
    }

    #[Test]
    public function endReturnsSelf(): void
    {
        $this->builder->add('parent')->children();

        self::assertSame($this->builder, $this->builder->end());
    }

    #[Test]
    public function childrenThrowsWhenCurrentHasNoChildren(): void
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessageMatches('/no children/i');

        $this->builder->children();
    }

    #[Test]
    public function endThrowsWhenAlreadyAtRootLevel(): void
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessageMatches('/root level/i');

        $this->builder->end();
    }

    #[Test]
    public function deeplyNestedStructureBuildsCorrectly(): void
    {
        $this->builder
            ->add('a')
            ->children()
                ->add('a1')
                ->add('a2')
                ->children()
                    ->add('a2_1')
                ->end()
            ->end()
            ->add('b');

        $root = $this->builder->build();

        self::assertCount(2, $root);

        $a = $root->getFirstChild();
        self::assertSame('a', $a->getName());
        self::assertCount(2, $a);

        $a2 = $a->getLastChild();
        self::assertSame('a2', $a2->getName());
        self::assertCount(1, $a2);
        self::assertSame('a2_1', $a2->getFirstChild()->getName());

        self::assertSame('b', $root->getLastChild()->getName());
    }

    #[Test]
    public function childrenFollowsLastAddedItemAfterPrepend(): void
    {
        // After add(prepend:true), children() should still descend into getLastChild()
        $this->builder->add('a');
        $this->builder->add('b', prepend: true);
        // last child is 'a', not 'b'
        $this->builder->children()->add('a_child')->end();

        $root = $this->builder->build();
        $a = $root->getLastChild(); // 'a' is last

        self::assertSame('a', $a->getName());
        self::assertCount(1, $a);
        self::assertSame('a_child', $a->getFirstChild()->getName());
    }
}
