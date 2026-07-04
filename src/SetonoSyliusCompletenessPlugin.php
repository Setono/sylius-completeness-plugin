<?php

declare(strict_types=1);

namespace Setono\SyliusCompletenessPlugin;

use Setono\SyliusCompletenessPlugin\DependencyInjection\Compiler\RegisterAffectedProductsResolversPass;
use Setono\SyliusCompletenessPlugin\DependencyInjection\Compiler\RegisterCheckersPass;
use Setono\SyliusCompletenessPlugin\DependencyInjection\Compiler\RegisterExpressionFunctionProvidersPass;
use Setono\SyliusCompletenessPlugin\DependencyInjection\Compiler\RegisterRollupStrategiesPass;
use Sylius\Bundle\CoreBundle\Application\SyliusPluginTrait;
use Sylius\Bundle\ResourceBundle\AbstractResourceBundle;
use Sylius\Bundle\ResourceBundle\SyliusResourceBundle;
use Symfony\Component\DependencyInjection\ContainerBuilder;

final class SetonoSyliusCompletenessPlugin extends AbstractResourceBundle
{
    use SyliusPluginTrait;

    /**
     * @return list<string>
     */
    public function getSupportedDrivers(): array
    {
        return [SyliusResourceBundle::DRIVER_DOCTRINE_ORM];
    }

    public function build(ContainerBuilder $container): void
    {
        parent::build($container);

        $container->addCompilerPass(new RegisterCheckersPass());
        $container->addCompilerPass(new RegisterExpressionFunctionProvidersPass());
        $container->addCompilerPass(new RegisterAffectedProductsResolversPass());
        $container->addCompilerPass(new RegisterRollupStrategiesPass());
    }
}
