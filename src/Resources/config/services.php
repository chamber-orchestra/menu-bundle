<?php

declare(strict_types=1);

/*
 * This file is part of the ChamberOrchestra package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use ChamberOrchestra\MenuBundle\Factory\Extension\TranslationExtension;
use ChamberOrchestra\MenuBundle\Factory\Factory;
use ChamberOrchestra\MenuBundle\Matcher\Matcher;
use ChamberOrchestra\MenuBundle\NavigationFactory;
use ChamberOrchestra\MenuBundle\Twig\Helper\Helper;

return static function (ContainerConfigurator $container): void {
    $services = $container->services()
        ->defaults()
            ->autowire()
            ->autoconfigure()
            ->private()
    ;

    $services->load('ChamberOrchestra\\MenuBundle\\', '../../')
        ->exclude('../../{DependencyInjection,Resources,Exception,Navigation}');

    $services->set(Factory::class)
        ->call('addExtensions', [tagged_iterator('chamber_orchestra_menu.factory.extension')]);

    $services->set(NavigationFactory::class)
        ->arg('$options', ['namespace' => '%chamber_orchestra_menu.cache.namespace%'])
        ->call('addRuntimeExtensions', [tagged_iterator('chamber_orchestra_menu.factory.runtime_extension')]);

    $services->set(Matcher::class)
        ->call('addVoters', [tagged_iterator('chamber_orchestra_menu.matcher.voter')]);

    $services->set(TranslationExtension::class)
        ->arg('$defaultDomain', '%chamber_orchestra_menu.translation.domain%');

    $services->set(Helper::class)
        ->arg('$defaultTemplate', '%chamber_orchestra_menu.default_template%');
};
