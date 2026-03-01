<?php

declare(strict_types=1);

/*
 * This file is part of the ChamberOrchestra package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Tests\Unit\Factory\Extension;

use ChamberOrchestra\MenuBundle\Factory\Extension\DividerExtension;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class DividerExtensionTest extends TestCase
{
    private DividerExtension $ext;

    protected function setUp(): void
    {
        $this->ext = new DividerExtension();
    }

    #[Test]
    public function skipsOptionsWithoutDividerKey(): void
    {
        $options = ['label' => 'Scores'];

        self::assertSame($options, $this->ext->buildOptions($options));
    }

    #[Test]
    public function skipsWhenDividerIsFalse(): void
    {
        $options = ['divider' => false];

        self::assertSame($options, $this->ext->buildOptions($options));
    }

    #[Test]
    public function setsDividerTrueInExtras(): void
    {
        $result = $this->ext->buildOptions(['divider' => true]);

        self::assertTrue($result['extras']['divider']);
        self::assertArrayNotHasKey('divider', $result);
    }

    #[Test]
    public function preservesExistingExtras(): void
    {
        $result = $this->ext->buildOptions([
            'extras' => ['icon' => 'fa-music'],
            'divider' => true,
        ]);

        self::assertSame('fa-music', $result['extras']['icon']);
        self::assertTrue($result['extras']['divider']);
    }
}
