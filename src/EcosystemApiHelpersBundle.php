<?php

namespace Ecosystem\ApiHelpersBundle;

use Ecosystem\ApiHelpersBundle\Adapter\AdapterHandlerInterface;
use Psr\Container\ContainerInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\HttpKernel\Bundle\AbstractBundle;

class EcosystemApiHelpersBundle extends AbstractBundle
{
    public function loadExtension(
        array $config,
        ContainerConfigurator $containerConfigurator,
        ContainerBuilder $containerBuilder
    ): void {
        $containerBuilder->registerForAutoconfiguration(AdapterHandlerInterface::class)
            ->addTag('ecosystem.api_helpers.adapter_handler');

        $containerBuilder->setAlias(
            ContainerInterface::class,
            'service_container'
        );

        $containerConfigurator->import('../config/services.yaml');
    }
}
