<?php

declare(strict_types=1);

/*
 * This file is part of the ChamberOrchestra package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Tests\Unit\Factory\Extension;

use ChamberOrchestra\MenuBundle\Factory\Extension\LabelExtension;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class LabelExtensionTest extends TestCase
{
    private LabelExtension $ext;

    protected function setUp(): void
    {
        $this->ext = new LabelExtension();
    }

    #[Test]
    public function setsLabelFromKeyWhenLabelAbsent(): void
    {
        $result = $this->ext->buildOptions(['key' => 'home']);

        self::assertSame('home', $result['label']);
    }

    #[Test]
    public function setsEmptyStringWhenBothLabelAndKeyAbsent(): void
    {
        $result = $this->ext->buildOptions([]);

        self::assertSame('', $result['label']);
    }

    #[Test]
    public function keepsExplicitLabelOverKey(): void
    {
        $result = $this->ext->buildOptions(['label' => 'Home', 'key' => 'home']);

        self::assertSame('Home', $result['label']);
    }

    #[Test]
    public function preservesOtherOptions(): void
    {
        $result = $this->ext->buildOptions(['label' => 'Home', 'uri' => '/']);

        self::assertSame('/', $result['uri']);
    }
}
