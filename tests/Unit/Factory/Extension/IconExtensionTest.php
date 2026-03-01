<?php

declare(strict_types=1);

/*
 * This file is part of the ChamberOrchestra package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Tests\Unit\Factory\Extension;

use ChamberOrchestra\MenuBundle\Factory\Extension\IconExtension;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class IconExtensionTest extends TestCase
{
    private IconExtension $ext;

    protected function setUp(): void
    {
        $this->ext = new IconExtension();
    }

    #[Test]
    public function skipsOptionsWithoutIcon(): void
    {
        $options = ['label' => 'Scores'];

        self::assertSame($options, $this->ext->buildOptions($options));
    }

    #[Test]
    public function movesIconToExtras(): void
    {
        $result = $this->ext->buildOptions(['icon' => 'fa-music']);

        self::assertSame('fa-music', $result['extras']['icon']);
        self::assertArrayNotHasKey('icon', $result);
    }

    #[Test]
    public function preservesExistingExtras(): void
    {
        $result = $this->ext->buildOptions([
            'extras' => ['badge' => 3],
            'icon' => 'fa-violin',
        ]);

        self::assertSame(3, $result['extras']['badge']);
        self::assertSame('fa-violin', $result['extras']['icon']);
    }
}
