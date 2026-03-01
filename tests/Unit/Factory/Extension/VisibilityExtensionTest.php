<?php

declare(strict_types=1);

/*
 * This file is part of the ChamberOrchestra package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Tests\Unit\Factory\Extension;

use ChamberOrchestra\MenuBundle\Factory\Extension\VisibilityExtension;
use ChamberOrchestra\MenuBundle\Menu\Item;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class VisibilityExtensionTest extends TestCase
{
    private VisibilityExtension $ext;

    protected function setUp(): void
    {
        $this->ext = new VisibilityExtension();
    }

    #[Test]
    public function skipsItemWithoutVisibleOption(): void
    {
        $item = new Item('scores', ['label' => 'Scores']);
        $this->ext->processItem($item);

        self::assertNull($item->getOption('extras')['visible'] ?? null);
    }

    #[Test]
    public function storesFalseInExtras(): void
    {
        $item = new Item('scores', ['visible' => false]);
        $this->ext->processItem($item);

        self::assertFalse($item->getOption('extras')['visible']);
    }

    #[Test]
    public function storesTrueInExtras(): void
    {
        $item = new Item('scores', ['visible' => true]);
        $this->ext->processItem($item);

        self::assertTrue($item->getOption('extras')['visible']);
    }

    #[Test]
    public function resolvesClosureAndStoresResult(): void
    {
        $item = new Item('scores', ['visible' => static fn (): bool => false]);
        $this->ext->processItem($item);

        self::assertFalse($item->getOption('extras')['visible']);
    }

    #[Test]
    public function preservesExistingExtras(): void
    {
        $item = new Item('scores', [
            'extras' => ['icon' => 'fa-music'],
            'visible' => true,
        ]);
        $this->ext->processItem($item);

        self::assertSame('fa-music', $item->getOption('extras')['icon']);
        self::assertTrue($item->getOption('extras')['visible']);
    }
}
