<?php

declare(strict_types=1);

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use ChamberOrchestra\MenuBundle\Factory\Extension\BadgeExtension;
use ChamberOrchestra\MenuBundle\Factory\Extension\CoreExtension;
use ChamberOrchestra\MenuBundle\Factory\Extension\ExtensionInterface;
use ChamberOrchestra\MenuBundle\Factory\Factory;
use ChamberOrchestra\MenuBundle\Matcher\Matcher;
use ChamberOrchestra\MenuBundle\Navigation\NavigationInterface;

return static function (ContainerConfigurator $container): void {
    $services = $container->services()
        ->defaults()
            ->autowire()
            ->autoconfigure()
            ->private()

        ->instanceof(ExtensionInterface::class)
            ->tag('chamber_orchestra_menu.factory.extension')

        ->instanceof(NavigationInterface::class)
            ->tag('chamber_orchestra_menu.navigation')
    ;

    $services->load('ChamberOrchestra\\MenuBundle\\', '../../')
        ->exclude('../../{DependencyInjection,Resources,Exception,Navigation}');

    $services->set(Factory::class)
        ->call('addExtensions', [\tagged_iterator('chamber_orchestra_menu.factory.extension')]);

    $services->set(BadgeExtension::class);

    $services->set(CoreExtension::class)
        ->tag('chamber_orchestra_menu.factory.extension', ['priority' => -10]);

    $services->set(Matcher::class)
        ->call('addVoters', [\tagged_iterator('chamber_orchestra_menu.matcher.voter')]);
};
