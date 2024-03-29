<?php

namespace DataDog\AuditBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder(): TreeBuilder
    {
        // @formatter:off
        $treeBuilder = new TreeBuilder('data_dog_audit');
        $treeBuilder->getRootNode()
            ->children()
                ->arrayNode('audited_entities')
                    ->canBeUnset()
                    ->performNoDeepMerging()
                    ->scalarPrototype()->end()
                ->end()
            ->end()
            ->children()
                ->arrayNode('unaudited_entities')
                    ->canBeUnset()
                    ->performNoDeepMerging()
                    ->scalarPrototype()->end()
                ->end()
            ->end()
            ->children()
                ->booleanNode('blame_impersonator')
                ->defaultFalse()
            ->end()
        ;
        // @formatter:on

        return $treeBuilder;
    }
}
