<?php

declare(strict_types=1);

/*
 * This file is part of the ChamberOrchestra package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Tests\Unit\Factory\Extension;

use ChamberOrchestra\MenuBundle\Factory\Extension\TranslationExtension;
use ChamberOrchestra\MenuBundle\Menu\Item;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Symfony\Contracts\Translation\TranslatorInterface;

final class TranslationExtensionTest extends TestCase
{
    #[Test]
    public function translatesLabelWithDefaultDomain(): void
    {
        $translator = $this->createMock(TranslatorInterface::class);
        $translator->expects(self::once())
            ->method('trans')
            ->with('menu.scores', [], 'messages')
            ->willReturn('Partituren');

        $ext = new TranslationExtension($translator);
        $item = new Item('scores', ['label' => 'menu.scores']);
        $ext->processItem($item);

        self::assertSame('Partituren', $item->getLabel());
    }

    #[Test]
    public function usesPerItemTranslationDomain(): void
    {
        $translator = $this->createMock(TranslatorInterface::class);
        $translator->expects(self::once())
            ->method('trans')
            ->with('menu.rehearsals', [], 'navigation')
            ->willReturn('Proben');

        $ext = new TranslationExtension($translator);
        $item = new Item('rehearsals', [
            'label' => 'menu.rehearsals',
            'translation_domain' => 'navigation',
        ]);
        $ext->processItem($item);

        self::assertSame('Proben', $item->getLabel());
    }

    #[Test]
    public function skipsEmptyLabel(): void
    {
        $translator = $this->createMock(TranslatorInterface::class);
        $translator->expects(self::never())->method('trans');

        $ext = new TranslationExtension($translator);
        $item = new Item('scores');
        $ext->processItem($item);

        self::assertSame('', $item->getLabel());
    }

    #[Test]
    public function usesCustomDefaultDomain(): void
    {
        $translator = $this->createMock(TranslatorInterface::class);
        $translator->expects(self::once())
            ->method('trans')
            ->with('menu.compositions', [], 'orchestra')
            ->willReturn('Kompositionen');

        $ext = new TranslationExtension($translator, 'orchestra');
        $item = new Item('compositions', ['label' => 'menu.compositions']);
        $ext->processItem($item);

        self::assertSame('Kompositionen', $item->getLabel());
    }
}
