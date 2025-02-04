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
            ->validate()
                ->ifTrue(fn ($v) => !empty($v['entities']) && (!empty($v['audited_entities']) || !empty($v['unaudited_entities'])))
                ->thenInvalid('If you use the "entities" config you cannot use "audited_entities" and/or "unaudited_entities"')
            ->end()
            ->children()
                ->arrayNode('entities')->canBeUnset()->useAttributeAsKey('key')
                    ->arrayPrototype()
                        ->children()
                            ->enumNode('mode')->values(['include', 'exclude'])->isRequired()->cannotBeEmpty()->end()
                            ->arrayNode('fields')
                                ->prototype('scalar')->end()
                            ->end()
                        ->end()
                    ->end()
                ->end()
            ->end()
            ->children()
                ->arrayNode('audited_entities')
                    ->setDeprecated('data-dog/audit-bundle', 'v1.2', 'Not setting the "%node%" config option is deprecated. Use the "entities" option instead.')
                    ->canBeUnset()
                    ->performNoDeepMerging()
                    ->scalarPrototype()->end()
                ->end()
            ->end()
            ->children()
                ->arrayNode('unaudited_entities')
                    ->setDeprecated('data-dog/audit-bundle', 'v1.2', 'Not setting the "%node%" config option is deprecated. Use the "entities" option instead.')
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
