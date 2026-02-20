<?php

declare(strict_types=1);

namespace ChamberOrchestra\MenuBundle\Factory\Extension;

use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;

#[AutoconfigureTag('chamber_orchestra_menu.factory.extension', ['priority' => -10])]
class CoreExtension implements ExtensionInterface
{
    /**
     * Builds the full option array used to configure the item.
     *
     * @param array<string, mixed> $options
     *
     * @return array<string, mixed>
     */
    public function buildOptions(array $options): array
    {
        return \array_merge([
            'uri' => null,
            'extras' => [],
            'current' => null,
            'attributes' => [],
        ], $options);
    }
}
