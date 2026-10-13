<?php

declare(strict_types=1);

namespace DataDog\AuditBundle\Tests\DependencyInjection;

use DataDog\AuditBundle\DependencyInjection\DataDogAuditExtension;
use DataDog\AuditBundle\Tests\Entity\Post;
use DataDog\AuditBundle\Tests\Entity\Tag;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;

final class DataDogAuditExtensionTest extends TestCase
{
    /**
     * @dataProvider provideEntityConfigurations
     */
    public function testEntityConfigurationReachesTheListener(array $config, array $expectedMethodCalls): void
    {
        $container = new ContainerBuilder();

        (new DataDogAuditExtension())->load([$config], $container);

        $methodCalls = $container->getDefinition('data_dog_audit.listener.audit')->getMethodCalls();
        $entityMethodCalls = array_values(array_filter(
            $methodCalls,
            static fn (array $methodCall): bool => $methodCall[0] !== 'setBlameImpersonator',
        ));

        $this->assertSame($expectedMethodCalls, $entityMethodCalls);
    }

    public function provideEntityConfigurations(): iterable
    {
        yield 'entities' => [
            ['entities' => [Tag::class => ['mode' => 'exclude', 'fields' => []]]],
            [['addEntities', [[Tag::class => ['mode' => 'exclude', 'fields' => []]]]]],
        ];

        yield 'audited_entities' => [
            ['audited_entities' => [Tag::class, Post::class]],
            [['addAuditedEntities', [[Tag::class, Post::class]]]],
        ];

        yield 'unaudited_entities' => [
            ['unaudited_entities' => [Tag::class]],
            [['addUnauditedEntities', [[Tag::class]]]],
        ];

        yield 'no entity configuration' => [
            [],
            [],
        ];
    }
}
