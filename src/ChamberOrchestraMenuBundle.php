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
use ChamberOrchestra\MenuBundle\Factory\Extension\TranslationExtension;
use ChamberOrchestra\MenuBundle\Matcher\Voter\VoterInterface;
use ChamberOrchestra\MenuBundle\Navigation\NavigationInterface;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;
use Symfony\Contracts\Translation\TranslatorInterface;

final class ChamberOrchestraMenuBundle extends Bundle implements CompilerPassInterface
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

        $container->registerForAutoconfiguration(VoterInterface::class)
            ->addTag('chamber_orchestra_menu.matcher.voter');

        $container->addCompilerPass($this);
    }

    public function process(ContainerBuilder $container): void
    {
        if (!$container->has(TranslatorInterface::class)) {
            $container->removeDefinition(TranslationExtension::class);
        }
    }
}
