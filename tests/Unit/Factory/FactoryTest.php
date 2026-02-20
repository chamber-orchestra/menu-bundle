<?php

declare(strict_types=1);

/*
 * This file is part of the ChamberOrchestra package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Tests\Unit\Factory;

use ChamberOrchestra\MenuBundle\Factory\Extension\ExtensionInterface;
use ChamberOrchestra\MenuBundle\Factory\Factory;
use ChamberOrchestra\MenuBundle\Menu\Item;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class FactoryTest extends TestCase
{
    #[Test]
    public function createItemReturnsItemInstance(): void
    {
        self::assertInstanceOf(Item::class, (new Factory())->createItem('home'));
    }

    #[Test]
    public function createItemSetsCorrectName(): void
    {
        self::assertSame('home', (new Factory())->createItem('home')->getName());
    }

    #[Test]
    public function createItemInjectsKeyFromName(): void
    {
        self::assertSame('home', (new Factory())->createItem('home')->getOption('key'));
    }

    #[Test]
    public function createItemPassesOptionsToItem(): void
    {
        $item = (new Factory())->createItem('home', ['label' => 'Home', 'uri' => '/']);

        self::assertSame('Home', $item->getLabel());
        self::assertSame('/', $item->getUri());
    }

    #[Test]
    public function createItemWithSectionFlag(): void
    {
        self::assertTrue((new Factory())->createItem('section', [], true)->isSection());
    }

    #[Test]
    public function extensionsAreAppliedInDescendingPriorityOrder(): void
    {
        $log = [];
        $factory = new Factory();
        $factory->addExtension($this->makeLogExtension($log, 'low'), priority: 0);
        $factory->addExtension($this->makeLogExtension($log, 'high'), priority: 10);
        $factory->addExtension($this->makeLogExtension($log, 'mid'), priority: 5);

        $factory->createItem('x');

        self::assertSame(['high', 'mid', 'low'], $log);
    }

    #[Test]
    public function addExtensionsAddsMultiple(): void
    {
        $log = [];
        $factory = new Factory();
        $factory->addExtensions([
            $this->makeLogExtension($log, 'a'),
            $this->makeLogExtension($log, 'b'),
        ]);

        $factory->createItem('x');

        self::assertSame(['a', 'b'], $log);
    }

    #[Test]
    public function sortedExtensionsCachedAcrossMultipleCreateCalls(): void
    {
        $log = [];
        $factory = new Factory();
        $factory->addExtension($this->makeLogExtension($log, 'first'), priority: 1);
        $factory->addExtension($this->makeLogExtension($log, 'second'), priority: 2);

        $factory->createItem('a');
        $factory->createItem('b');

        // Order must be consistent across multiple creates
        self::assertSame(['second', 'first', 'second', 'first'], $log);
    }

    #[Test]
    public function addingExtensionAfterCreateInvalidatesSortCache(): void
    {
        $log = [];
        $factory = new Factory();
        $factory->addExtension($this->makeLogExtension($log, 'a'), priority: 0);
        $factory->createItem('x');

        $factory->addExtension($this->makeLogExtension($log, 'b'), priority: 10);
        $factory->createItem('y');

        // After adding 'b' with higher priority, it should run first on second create
        self::assertSame(['a', 'b', 'a'], $log);
    }

    private function makeLogExtension(array &$log, string $name): ExtensionInterface
    {
        return new class($log, $name) implements ExtensionInterface {
            public function __construct(private array &$log, private readonly string $name)
            {
            }

            public function buildOptions(array $options = []): array
            {
                $this->log[] = $this->name;

                return $options;
            }
        };
    }
}
