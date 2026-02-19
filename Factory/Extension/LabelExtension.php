<?php

declare(strict_types=1);

namespace ChamberOrchestra\MenuBundle\Factory\Extension;

use Symfony\Contracts\Translation\TranslatorInterface;

class LabelExtension implements ExtensionInterface
{
    public function __construct(private readonly TranslatorInterface $translator)
    {
    }

    public function buildOptions(array $options = []): array
    {
        if (!isset($options['label'])) {
            $options['label'] = $options['key'] ?? '';
        }

        if (isset($options['translation_domain'])) {
            $options['label'] = $this->translator->trans($options['label'], [], $options['translation_domain']);
        }

        return $options;
    }
}
