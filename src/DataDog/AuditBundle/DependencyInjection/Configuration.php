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
                    ->prototype('scalar')->end()
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

        return $treeBuilder;
    }

}
