<?php

declare(strict_types=1);

/*
 * This file is part of the ChamberOrchestra package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ChamberOrchestra\MenuBundle\Factory\Extension;

use ChamberOrchestra\MenuBundle\Menu\Item;
use Symfony\Contracts\Translation\TranslatorInterface;

class TranslationExtension implements RuntimeExtensionInterface
{
    public function __construct(
        private readonly TranslatorInterface $translator,
        private readonly string $defaultDomain = 'messages',
    ) {
    }

    public function processItem(Item $item): void
    {
        $label = $item->getLabel();

        if ('' === $label) {
            return;
        }

        /** @var string $domain */
        $domain = $item->getOption('translation_domain', $this->defaultDomain);

        $item->setLabel($this->translator->trans($label, [], $domain));
    }
}
