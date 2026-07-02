<?php

declare(strict_types=1);

namespace Setono\SyliusCompletenessPlugin\DependencyInjection;

use Setono\SyliusCompletenessPlugin\Checker\CompletenessCheckerInterface;
use Sylius\Bundle\ResourceBundle\DependencyInjection\Extension\AbstractResourceExtension;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\PrependExtensionInterface;
use Symfony\Component\DependencyInjection\Loader\XmlFileLoader;

final class SetonoSyliusCompletenessExtension extends AbstractResourceExtension implements PrependExtensionInterface
{
    public function load(array $configs, ContainerBuilder $container): void
    {
        /**
         * @var array{
         *     driver: string,
         *     rollup_strategy: string,
         *     default_channel_code: ?string,
         *     default_ready_threshold: int,
         *     amber_band: int,
         *     weight_tiers: array<string, float>,
         *     enable_custom_weight: bool,
         *     recalculate_on_doctrine_flush: bool,
         *     bulk_threshold: int,
         *     resources: array<string, mixed>,
         * } $config
         */
        $config = $this->processConfiguration(new Configuration(), $configs);
        $loader = new XmlFileLoader($container, new FileLocator(__DIR__ . '/../Resources/config'));

        $container->setParameter('setono_sylius_completeness.rollup_strategy', $config['rollup_strategy']);
        $container->setParameter('setono_sylius_completeness.default_channel_code', $config['default_channel_code']);
        $container->setParameter('setono_sylius_completeness.default_ready_threshold', $config['default_ready_threshold']);
        $container->setParameter('setono_sylius_completeness.amber_band', $config['amber_band']);
        $container->setParameter('setono_sylius_completeness.weight_tiers', $config['weight_tiers']);
        $container->setParameter('setono_sylius_completeness.enable_custom_weight', $config['enable_custom_weight']);
        $container->setParameter('setono_sylius_completeness.recalculate_on_doctrine_flush', $config['recalculate_on_doctrine_flush']);
        $container->setParameter('setono_sylius_completeness.bulk_threshold', $config['bulk_threshold']);

        $loader->load('services.xml');

        $container->registerForAutoconfiguration(CompletenessCheckerInterface::class)
            ->addTag('setono_sylius_completeness.checker');

        $this->registerResources('setono_sylius_completeness', $config['driver'], $config['resources'], $container);
    }

    public function prepend(ContainerBuilder $container): void
    {
        $container->prependExtensionConfig('framework', [
            'messenger' => [
                'buses' => [
                    'setono_sylius_completeness.command_bus' => null,
                ],
            ],
        ]);
    }
}
