<?php

declare(strict_types=1);

namespace DataDog\AuditBundle\Tests;

use DataDog\AuditBundle\DataDogAuditBundle;
use Doctrine\Bundle\DoctrineBundle\DoctrineBundle;
use Psr\Log\NullLogger;
use Symfony\Bundle\FrameworkBundle\FrameworkBundle;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Kernel;

class TestKernel extends Kernel
{
    private ?string $projectDir = null;

    public function __construct()
    {
        parent::__construct('test', true);
    }

    public function registerBundles(): iterable
    {
        return [
            new FrameworkBundle(),
            new DoctrineBundle(),
            new DataDogAuditBundle(),
        ];
    }

    public function registerContainerConfiguration(LoaderInterface $loader): void
    {
        $loader->load(function (ContainerBuilder $container): void {
            $container->loadFromExtension('framework');
            $container->loadFromExtension('doctrine', [
                'dbal' => ['driver' => 'pdo_sqlite'],
                'orm' => [
                    'auto_generate_proxy_classes' => true,
                    'mappings' => [
                        'DataDogAuditBundle' => [
                            'dir' => __DIR__.'/../src/DataDog/AuditBundle/Entity',
                            'prefix' => 'DataDog\AuditBundle\Entity',
                        ],
                        'DataDogAuditBundleFixtures' => [
                            'dir' => __DIR__.'/Entity',
                            'prefix' => 'DataDog\AuditBundle\Tests\Entity',
                        ],
                    ],
                ],
            ]);
            $container->loadFromExtension('data_dog_audit');

            $container->register('logger', NullLogger::class);
        });
    }

    public function getProjectDir(): string
    {
        if ($this->projectDir === null) {
            $this->projectDir = sys_get_temp_dir().'/sf_kernel_'.md5((string)mt_rand());
        }

        return $this->projectDir;
    }
}
