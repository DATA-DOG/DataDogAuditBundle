<?php

namespace DataDog\AuditBundle;

use Doctrine\Bundle\DoctrineBundle\DependencyInjection\Compiler\DoctrineOrmMappingsPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class DataDogAuditBundle extends Bundle
{
    public function build(ContainerBuilder $container): void
    {
        parent::build($container);

        if (class_exists(DoctrineOrmMappingsPass::class)) {
            $namespaces = [__DIR__.'/../config/doctrine' => 'DataDog\\AuditBundle\\Entity'];
            $container->addCompilerPass(
                DoctrineOrmMappingsPass::createXmlMappingDriver($namespaces)
            );
        }
    }
}
