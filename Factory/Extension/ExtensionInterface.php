<?php

declare(strict_types=1);

namespace ChamberOrchestra\MenuBundle\Factory\Extension;

interface ExtensionInterface
{
    /**
     * Builds the full option array used to configure the item.
     */
    public function buildOptions(array $options): array;
}
