<?php

declare(strict_types=1);

namespace ChamberOrchestra\MenuBundle\Factory\Extension;

use Symfony\Contracts\Translation\TranslatorInterface;

class LabelExtension implements ExtensionInterface
{
    public function __construct(private readonly TranslatorInterface $translator)
    {
    }

    /**
     * @param array<string, mixed> $options
     *
     * @return array<string, mixed>
     */
    public function buildOptions(array $options = []): array
    {
        if (!isset($options['label'])) {
            /** @var string $key */
            $key = $options['key'] ?? '';
            $options['label'] = $key;
        }

        if (isset($options['translation_domain'])) {
            /** @var string $label */
            $label = $options['label'];
            /** @var string $domain */
            $domain = $options['translation_domain'];
            $options['label'] = $this->translator->trans($label, [], $domain);
        }

        return $options;
    }
}
