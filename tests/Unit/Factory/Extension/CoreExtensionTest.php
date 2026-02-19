<?php

declare(strict_types=1);

namespace Tests\Unit\Factory\Extension;

use ChamberOrchestra\MenuBundle\Factory\Extension\CoreExtension;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class CoreExtensionTest extends TestCase
{
    private CoreExtension $ext;

    protected function setUp(): void
    {
        $this->ext = new CoreExtension();
    }

    #[Test]
    public function setsNullUriDefault(): void
    {
        self::assertNull($this->ext->buildOptions([])['uri']);
    }

    #[Test]
    public function setsEmptyExtrasDefault(): void
    {
        self::assertSame([], $this->ext->buildOptions([])['extras']);
    }

    #[Test]
    public function setsNullCurrentDefault(): void
    {
        self::assertNull($this->ext->buildOptions([])['current']);
    }

    #[Test]
    public function setsEmptyAttributesDefault(): void
    {
        self::assertSame([], $this->ext->buildOptions([])['attributes']);
    }

    #[Test]
    public function doesNotOverrideProvidedUri(): void
    {
        $result = $this->ext->buildOptions(['uri' => '/home']);

        self::assertSame('/home', $result['uri']);
    }

    #[Test]
    public function doesNotOverrideProvidedExtras(): void
    {
        $result = $this->ext->buildOptions(['extras' => ['icon' => 'star']]);

        self::assertSame(['icon' => 'star'], $result['extras']);
    }

    #[Test]
    public function doesNotOverrideProvidedAttributes(): void
    {
        $result = $this->ext->buildOptions(['attributes' => ['class' => 'active']]);

        self::assertSame(['class' => 'active'], $result['attributes']);
    }

    #[Test]
    public function preservesUnknownOptions(): void
    {
        $result = $this->ext->buildOptions(['custom_key' => 'value']);

        self::assertSame('value', $result['custom_key']);
    }
}
