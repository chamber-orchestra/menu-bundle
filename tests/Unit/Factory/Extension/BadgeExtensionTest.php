<?php

declare(strict_types=1);

namespace Tests\Unit\Factory\Extension;

use ChamberOrchestra\MenuBundle\Factory\Extension\BadgeExtension;
use ChamberOrchestra\MenuBundle\Menu\Item;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class BadgeExtensionTest extends TestCase
{
    private BadgeExtension $ext;

    protected function setUp(): void
    {
        $this->ext = new BadgeExtension();
    }

    #[Test]
    public function skipsItemWithoutBadgeOption(): void
    {
        $item = new Item('scores', ['label' => 'Scores']);
        $this->ext->processItem($item);

        self::assertNull($item->getBadge());
    }

    #[Test]
    public function intBadgeIsMovedToExtras(): void
    {
        $item = new Item('scores', ['badge' => 5]);
        $this->ext->processItem($item);

        self::assertSame(5, $item->getBadge());
    }

    #[Test]
    public function closureBadgeIsResolvedAndMovedToExtras(): void
    {
        $item = new Item('scores', ['badge' => static fn (): int => 42]);
        $this->ext->processItem($item);

        self::assertSame(42, $item->getBadge());
    }

    #[Test]
    public function zeroBadgeIsPreserved(): void
    {
        $item = new Item('scores', ['badge' => 0]);
        $this->ext->processItem($item);

        self::assertSame(0, $item->getBadge());
    }

    #[Test]
    public function closureReturningZeroIsPreserved(): void
    {
        $item = new Item('scores', ['badge' => static fn (): int => 0]);
        $this->ext->processItem($item);

        self::assertSame(0, $item->getBadge());
    }

    #[Test]
    public function existingExtrasArePreserved(): void
    {
        $item = new Item('scores', [
            'extras' => ['icon' => 'rehearsal'],
            'badge' => 3,
        ]);
        $this->ext->processItem($item);

        self::assertSame('rehearsal', $item->getOption('extras')['icon']);
        self::assertSame(3, $item->getBadge());
    }
}
