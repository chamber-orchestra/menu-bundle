<?php

declare(strict_types=1);

namespace Tests\Unit\Factory\Extension;

use ChamberOrchestra\MenuBundle\Factory\Extension\BadgeExtension;
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
    public function returnsOptionsUnchangedWhenBadgeMissing(): void
    {
        $options = ['label' => 'Scores'];

        self::assertSame($options, $this->ext->buildOptions($options));
    }

    #[Test]
    public function intBadgeIsMovedToExtras(): void
    {
        $result = $this->ext->buildOptions(['badge' => 5]);

        self::assertArrayNotHasKey('badge', $result);
        self::assertSame(5, $result['extras']['badge']);
    }

    #[Test]
    public function closureBadgeIsResolvedAndMovedToExtras(): void
    {
        $result = $this->ext->buildOptions(['badge' => static fn (): int => 42]);

        self::assertArrayNotHasKey('badge', $result);
        self::assertSame(42, $result['extras']['badge']);
    }

    #[Test]
    public function zeroBadgeIsPreserved(): void
    {
        $result = $this->ext->buildOptions(['badge' => 0]);

        self::assertSame(0, $result['extras']['badge']);
    }

    #[Test]
    public function closureReturningZeroIsPreserved(): void
    {
        $result = $this->ext->buildOptions(['badge' => static fn (): int => 0]);

        self::assertSame(0, $result['extras']['badge']);
    }

    #[Test]
    public function existingExtrasArePreserved(): void
    {
        $result = $this->ext->buildOptions([
            'extras' => ['icon' => 'rehearsal'],
            'badge' => 3,
        ]);

        self::assertSame('rehearsal', $result['extras']['icon']);
        self::assertSame(3, $result['extras']['badge']);
    }
}
