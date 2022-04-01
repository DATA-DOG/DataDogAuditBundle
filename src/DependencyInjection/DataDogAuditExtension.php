<?php

namespace DataDog\AuditBundle\DependencyInjection;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\PhpFileLoader;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;

class DataDogAuditExtension extends Extension
{
    public function load(array $configs, ContainerBuilder $container): void
    {
        $loader = new PhpFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));
        $loader->load('services.php');

        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        $auditSubscriber = $container->getDefinition('datadog.event_subscriber.audit');

        if (isset($config['audited_entities']) && !empty($config['audited_entities'])) {
            $auditSubscriber->addMethodCall('addAuditedEntities', array($config['audited_entities']));
        } else if (isset($config['unaudited_entities'])) {
            $auditSubscriber->addMethodCall('addUnauditedEntities', array($config['unaudited_entities']));
        }

        if (isset($config['blame_impersonator'])) {
            $auditSubscriber->addMethodCall('setBlameImpersonator', array($config['blame_impersonator']));
        }

        if (isset($config['log_user_ip'])) {
            $auditSubscriber->addMethodCall('setLogIp', array($config['log_user_ip']));
        }
        if (isset($config['log_user_agent'])) {
            $auditSubscriber->addMethodCall('setLogUserAgent', array($config['log_user_agent']));
        }
        if (isset($config['truncate_user_agent'])) {
            $auditSubscriber->addMethodCall('setTruncateUserAgent', array($config['truncate_user_agent']));
        }
    }
}
