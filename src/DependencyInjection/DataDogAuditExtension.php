<?php

namespace DataDog\AuditBundle\DependencyInjection;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\PhpFileLoader;
use Symfony\Component\DependencyInjection\Extension\Extension;

class DataDogAuditExtension extends Extension
{
    public function load(array $configs, ContainerBuilder $container): void
    {
        $loader = new PhpFileLoader($container, new FileLocator(__DIR__.'/../../config'));
        $loader->load('services.php');

        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        $auditListener = $container->getDefinition('data_dog_audit.listener.audit');

        if (isset($config['entities'])) {
            $auditListener->addMethodCall('addEntities', [$config['entities']]);
        } elseif (isset($config['audited_entities']) && !empty($config['audited_entities'])) {
            $auditListener->addMethodCall('addAuditedEntities', [$config['audited_entities']]);
        } elseif (isset($config['unaudited_entities'])) {
            $auditListener->addMethodCall('addUnauditedEntities', [$config['unaudited_entities']]);
        }

        if (isset($config['blame_impersonator'])) {
            $auditListener->addMethodCall('setBlameImpersonator', [$config['blame_impersonator']]);
        }
    }
}
