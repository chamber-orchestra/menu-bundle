<?php

declare(strict_types=1);

/*
 * This file is part of the ChamberOrchestra package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Tests\Unit\Factory\Extension;

use ChamberOrchestra\MenuBundle\Factory\Extension\CounterExtension;
use ChamberOrchestra\MenuBundle\Menu\Item;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class CounterExtensionTest extends TestCase
{
    private CounterExtension $ext;

    protected function setUp(): void
    {
        $this->ext = new CounterExtension();
    }

    #[Test]
    public function skipsItemWithoutCountersOption(): void
    {
        $item = new Item('scores', ['label' => 'Scores']);
        $this->ext->processItem($item);

        self::assertNull($item->getOption('extras')['counters'] ?? null);
    }

    #[Test]
    public function storesIntValuesDirectly(): void
    {
        $item = new Item('scores', ['counters' => ['rehearsals' => 5, 'compositions' => 12]]);
        $this->ext->processItem($item);

        /** @var array<string, int> $counters */
        $counters = $item->getOption('extras')['counters'];
        self::assertSame(['rehearsals' => 5, 'compositions' => 12], $counters);
    }

    #[Test]
    public function resolvesClosureValues(): void
    {
        $item = new Item('scores', [
            'counters' => [
                'rehearsals' => static fn (): int => 7,
                'compositions' => 3,
            ],
        ]);
        $this->ext->processItem($item);

        /** @var array<string, int> $counters */
        $counters = $item->getOption('extras')['counters'];
        self::assertSame(['rehearsals' => 7, 'compositions' => 3], $counters);
    }

    #[Test]
    public function emptyArrayStoresEmptyCounters(): void
    {
        $item = new Item('scores', ['counters' => []]);
        $this->ext->processItem($item);

        self::assertSame([], $item->getOption('extras')['counters']);
    }
}
