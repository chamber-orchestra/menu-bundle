<?php

declare(strict_types=1);

/*
 * This file is part of the ChamberOrchestra package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ChamberOrchestra\MenuBundle\Factory\Extension;

class LabelExtension implements ExtensionInterface
{
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

        return $options;
    }
}
