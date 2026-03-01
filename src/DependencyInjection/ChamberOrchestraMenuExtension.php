<?php

declare(strict_types=1);

/*
 * This file is part of the ChamberOrchestra package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ChamberOrchestra\MenuBundle\DependencyInjection;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Loader\PhpFileLoader;

final class ChamberOrchestraMenuExtension extends Extension
{
    /**
     * @param array<array<string, mixed>> $configs
     */
    public function load(array $configs, ContainerBuilder $container): void
    {
        $configuration = new Configuration();
        /** @var array{default_template: ?string, translation: array{domain: string}, cache: array{namespace: string}} $config */
        $config = $this->processConfiguration($configuration, $configs);

        $container->setParameter('chamber_orchestra_menu.default_template', $config['default_template']);
        $container->setParameter('chamber_orchestra_menu.translation.domain', $config['translation']['domain']);
        $container->setParameter('chamber_orchestra_menu.cache.namespace', $config['cache']['namespace']);

        $loader = new PhpFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));
        $loader->load('services.php');
    }
}
