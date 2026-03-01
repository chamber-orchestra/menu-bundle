<?php

declare(strict_types=1);

/*
 * This file is part of the ChamberOrchestra package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ChamberOrchestra\MenuBundle\Factory\Extension;

class DividerExtension implements ExtensionInterface
{
    /**
     * @param array<string, mixed> $options
     *
     * @return array<string, mixed>
     */
    public function buildOptions(array $options): array
    {
        if (empty($options['divider'])) {
            return $options;
        }

        /** @var array<string, mixed> $extras */
        $extras = $options['extras'] ?? [];
        $extras['divider'] = true;
        $options['extras'] = $extras;

        unset($options['divider']);

        return $options;
    }
}
