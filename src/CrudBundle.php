<?php

namespace Coen\CrudBundle;

use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\AbstractBundle;

class CrudBundle extends AbstractBundle
{
    public function loadExtension(array $config, ContainerConfigurator $containerConfigurator, ContainerBuilder $containerBuilder): void
    {
        $servicesPath = __DIR__ . '/../config/services.yaml';
        $containerConfigurator->import($servicesPath);
    }
}