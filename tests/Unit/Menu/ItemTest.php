<?php

declare(strict_types=1);

namespace Tests\Unit\Menu;

use ChamberOrchestra\MenuBundle\Menu\Item;
use Doctrine\Common\Collections\Collection;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class ItemTest extends TestCase
{
    #[Test]
    public function getName(): void
    {
        self::assertSame('home', (new Item('home'))->getName());
    }

    #[Test]
    public function getLabelDefaultsToEmpty(): void
    {
        self::assertSame('', (new Item('home'))->getLabel());
    }

    #[Test]
    public function getLabel(): void
    {
        self::assertSame('Home', (new Item('home', ['label' => 'Home']))->getLabel());
    }

    #[Test]
    public function getUriDefaultsToNull(): void
    {
        self::assertNull((new Item('home'))->getUri());
    }

    #[Test]
    public function getUri(): void
    {
        self::assertSame('/home', (new Item('home', ['uri' => '/home']))->getUri());
    }

    #[Test]
    public function getRolesDefaultsToEmpty(): void
    {
        self::assertSame([], (new Item('home'))->getRoles());
    }

    #[Test]
    public function getRoles(): void
    {
        self::assertSame(['ROLE_ADMIN'], (new Item('home', ['roles' => ['ROLE_ADMIN']]))->getRoles());
    }

    #[Test]
    public function getOptionReturnsFallbackForMissingKey(): void
    {
        self::assertSame('default', (new Item('home'))->getOption('missing', 'default'));
    }

    #[Test]
    public function getOptionReturnsFallbackWhenKeyMissingAndDefaultIsNull(): void
    {
        self::assertNull((new Item('home'))->getOption('missing'));
    }

    #[Test]
    public function getOptionReturnsFalseWhenStoredAsFalse(): void
    {
        // array_key_exists check must distinguish false from missing
        $item = new Item('home', ['flag' => false]);
        self::assertFalse($item->getOption('flag', 'should-not-use-this'));
    }

    #[Test]
    public function isSectionFalseByDefault(): void
    {
        self::assertFalse((new Item('home'))->isSection());
    }

    #[Test]
    public function isSection(): void
    {
        self::assertTrue((new Item('section', [], true))->isSection());
    }

    #[Test]
    public function addAppendsAndReturnsSelf(): void
    {
        $item = new Item('root');
        $child = new Item('child');

        $result = $item->add($child);

        self::assertSame($item, $result);
        self::assertCount(1, $item);
        self::assertSame($child, $item->getFirstChild());
    }

    #[Test]
    public function addAppendsByDefault(): void
    {
        $item = new Item('root');
        $first = new Item('first');
        $second = new Item('second');
        $item->add($first);
        $item->add($second);

        self::assertSame($first, $item->getFirstChild());
        self::assertSame($second, $item->getLastChild());
    }

    #[Test]
    public function addWithPrependInsertsAtFront(): void
    {
        $item = new Item('root');
        $item->add(new Item('a'));
        $item->add(new Item('b'), prepend: true);

        self::assertSame('b', $item->getFirstChild()->getName());
        self::assertSame('a', $item->getLastChild()->getName());
    }

    #[Test]
    public function getFirstChildReturnsNullForEmpty(): void
    {
        self::assertNull((new Item('root'))->getFirstChild());
    }

    #[Test]
    public function getLastChildReturnsNullForEmpty(): void
    {
        self::assertNull((new Item('root'))->getLastChild());
    }

    #[Test]
    public function countReflectsChildren(): void
    {
        $item = new Item('root');
        self::assertSame(0, $item->count());
        $item->add(new Item('a'));
        $item->add(new Item('b'));
        self::assertSame(2, $item->count());
    }

    #[Test]
    public function getChildrenReturnsDoctrineCollection(): void
    {
        self::assertInstanceOf(Collection::class, (new Item('root'))->getChildren());
    }

    #[Test]
    public function isIterableViaForeach(): void
    {
        $item = new Item('root');
        $child = new Item('child');
        $item->add($child);

        $collected = [];
        foreach ($item as $c) {
            $collected[] = $c;
        }

        self::assertSame([$child], $collected);
    }

    #[Test]
    public function serializationRoundTripPreservesAllFields(): void
    {
        $original = new Item('root', ['label' => 'Root', 'roles' => ['ROLE_ADMIN']], true);
        $child = new Item('child', ['label' => 'Child']);
        $original->add($child);

        /** @var Item $restored */
        $restored = \unserialize(\serialize($original));

        self::assertSame('root', $restored->getName());
        self::assertSame('Root', $restored->getLabel());
        self::assertTrue($restored->isSection());
        self::assertSame(['ROLE_ADMIN'], $restored->getRoles());
        self::assertCount(1, $restored);
        self::assertSame('child', $restored->getFirstChild()->getName());
        self::assertSame('Child', $restored->getFirstChild()->getLabel());
    }

    #[Test]
    public function serializationPreservesSectionFalse(): void
    {
        $item = new Item('item', [], false);
        $restored = \unserialize(\serialize($item));

        self::assertFalse($restored->isSection());
    }

    #[Test]
    public function serializationPreservesNestedChildren(): void
    {
        $root = new Item('root');
        $child = new Item('child');
        $grandchild = new Item('grandchild');
        $child->add($grandchild);
        $root->add($child);

        /** @var Item $restored */
        $restored = \unserialize(\serialize($root));

        self::assertCount(1, $restored);
        self::assertCount(1, $restored->getFirstChild());
        self::assertSame('grandchild', $restored->getFirstChild()->getFirstChild()->getName());
    }
}
