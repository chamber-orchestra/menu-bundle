<?php

declare(strict_types=1);

/*
 * This file is part of the ChamberOrchestra package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ChamberOrchestra\MenuBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

final class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('chamber_orchestra_menu');

        $treeBuilder->getRootNode()
            ->children()
                ->scalarNode('default_template')
                    ->defaultNull()
                ->end()
                ->arrayNode('translation')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->scalarNode('domain')
                            ->defaultValue('messages')
                        ->end()
                    ->end()
                ->end()
                ->arrayNode('cache')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->scalarNode('namespace')
                            ->defaultValue('$NAVIGATION$')
                        ->end()
                    ->end()
                ->end()
            ->end()
        ;

        return $treeBuilder;
    }
}
