<?php

declare(strict_types=1);

/*
 * This file is part of the ChamberOrchestra package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use ChamberOrchestra\MenuBundle\Factory\Extension\ExtensionInterface;
use ChamberOrchestra\MenuBundle\Factory\Extension\RuntimeExtensionInterface;
use ChamberOrchestra\MenuBundle\Factory\Factory;
use ChamberOrchestra\MenuBundle\Matcher\Matcher;
use ChamberOrchestra\MenuBundle\Navigation\NavigationInterface;
use ChamberOrchestra\MenuBundle\NavigationFactory;

return static function (ContainerConfigurator $container): void {
    $services = $container->services()
        ->defaults()
            ->autowire()
            ->autoconfigure()
            ->private()

        ->instanceof(ExtensionInterface::class)
            ->tag('chamber_orchestra_menu.factory.extension')

        ->instanceof(RuntimeExtensionInterface::class)
            ->tag('chamber_orchestra_menu.factory.runtime_extension')

        ->instanceof(NavigationInterface::class)
            ->tag('chamber_orchestra_menu.navigation')
    ;

    $services->load('ChamberOrchestra\\MenuBundle\\', '../../')
        ->exclude('../../{DependencyInjection,Resources,Exception,Navigation}');

    $services->set(Factory::class)
        ->call('addExtensions', [tagged_iterator('chamber_orchestra_menu.factory.extension')]);

    $services->set(NavigationFactory::class)
        ->call('addRuntimeExtensions', [tagged_iterator('chamber_orchestra_menu.factory.runtime_extension')]);

    $services->set(Matcher::class)
        ->call('addVoters', [tagged_iterator('chamber_orchestra_menu.matcher.voter')]);
};
