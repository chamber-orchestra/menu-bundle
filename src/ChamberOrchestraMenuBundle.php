<?php

declare(strict_types=1);

/*
 * This file is part of the ChamberOrchestra package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ChamberOrchestra\MenuBundle;

use ChamberOrchestra\MenuBundle\Factory\Extension\ExtensionInterface;
use ChamberOrchestra\MenuBundle\Factory\Extension\RuntimeExtensionInterface;
use ChamberOrchestra\MenuBundle\Navigation\NavigationInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

final class ChamberOrchestraMenuBundle extends Bundle
{
    public function build(ContainerBuilder $container): void
    {
        parent::build($container);

        $container->registerForAutoconfiguration(NavigationInterface::class)
            ->addTag('chamber_orchestra_menu.navigation');

        $container->registerForAutoconfiguration(ExtensionInterface::class)
            ->addTag('chamber_orchestra_menu.factory.extension');

        $container->registerForAutoconfiguration(RuntimeExtensionInterface::class)
            ->addTag('chamber_orchestra_menu.factory.runtime_extension');
    }
}
