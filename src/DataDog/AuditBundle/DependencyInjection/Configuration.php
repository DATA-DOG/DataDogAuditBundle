<?php

namespace DataDog\AuditBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * Configuration for DataDog/AuditBundle
 */
class Configuration implements ConfigurationInterface
{

    /**
     * {@inheritDoc}
     * @see \Symfony\Component\Config\Definition\ConfigurationInterface::getConfigTreeBuilder()
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();
        $rootNode = $treeBuilder->root('data_dog_audit');

        $rootNode
            ->children()
                ->arrayNode('audited_entities')
                ->canBeUnset()
                ->performNoDeepMerging()
                ->prototype('array')
                    ->children()
                        ->variableNode('audited_properties')
                            ->defaultValue(array())
                            ->end()
                        ->variableNode('unaudited_properties')
                            ->defaultValue(array())
                            ->end()
                    ->end()
                ->end()
            ->end()
        ;

        $rootNode
            ->children()
                ->arrayNode('unaudited_entities')
                    ->canBeUnset()
                    ->performNoDeepMerging()
                    ->prototype('scalar')->end()
                ->end()
            ->end()
        ;

        $rootNode
            ->children()
                ->booleanNode('blame_impersonator')
                ->defaultFalse()
            ->end()
        ;

        return $treeBuilder;
    }

}
