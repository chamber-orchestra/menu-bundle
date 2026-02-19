<?php

declare(strict_types=1);

namespace Tests\Unit\Factory\Extension;

use ChamberOrchestra\MenuBundle\Factory\Extension\LabelExtension;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Symfony\Contracts\Translation\TranslatorInterface;

#[AllowMockObjectsWithoutExpectations]
final class LabelExtensionTest extends TestCase
{
    private TranslatorInterface $translator;
    private LabelExtension $ext;

    protected function setUp(): void
    {
        $this->translator = $this->createMock(TranslatorInterface::class);
        $this->ext = new LabelExtension($this->translator);
    }

    #[Test]
    public function setsLabelFromKeyWhenLabelAbsent(): void
    {
        $this->translator->expects(self::never())->method('trans');

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
    public function translatesLabelWhenDomainProvided(): void
    {
        $this->translator
            ->expects(self::once())
            ->method('trans')
            ->with('nav.home', [], 'navigation')
            ->willReturn('Главная');

        $result = $this->ext->buildOptions([
            'label' => 'nav.home',
            'translation_domain' => 'navigation',
        ]);

        self::assertSame('Главная', $result['label']);
    }

    #[Test]
    public function skipsTranslationWhenNoDomainProvided(): void
    {
        $this->translator->expects(self::never())->method('trans');

        $result = $this->ext->buildOptions(['label' => 'Home']);

        self::assertSame('Home', $result['label']);
    }

    #[Test]
    public function translatesKeyDerivedLabelWhenDomainProvided(): void
    {
        $this->translator
            ->expects(self::once())
            ->method('trans')
            ->with('home', [], 'menu')
            ->willReturn('Главная');

        $result = $this->ext->buildOptions([
            'key' => 'home',
            'translation_domain' => 'menu',
        ]);

        self::assertSame('Главная', $result['label']);
    }

    #[Test]
    public function preservesOtherOptions(): void
    {
        $result = $this->ext->buildOptions(['label' => 'Home', 'uri' => '/']);

        self::assertSame('/', $result['uri']);
    }
}
