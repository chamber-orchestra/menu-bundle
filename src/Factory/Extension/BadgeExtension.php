<?php

declare(strict_types=1);

namespace ChamberOrchestra\MenuBundle\Factory\Extension;

class BadgeExtension implements ExtensionInterface
{
    /**
     * @param array<string, mixed> $options
     *
     * @return array<string, mixed>
     */
    public function buildOptions(array $options): array
    {
        if (!\array_key_exists('badge', $options)) {
            return $options;
        }

        $badge = $options['badge'];
        unset($options['badge']);

        /** @var array<string, mixed> $extras */
        $extras = $options['extras'] ?? [];
        $extras['badge'] = $badge instanceof \Closure ? $badge() : $badge;
        $options['extras'] = $extras;

        return $options;
    }
}
