<?php

namespace Bigfoot\Bundle\ImportBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * This is the class that validates and merges configuration from your app/config files
 *
 * To learn more see {@link http://symfony.com/doc/current/cookbook/bundles/extension.html#cookbook-bundles-extension-config-class}
 */
class Configuration implements ConfigurationInterface
{
    /**
     * {@inheritDoc}
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();
        $rootNode = $treeBuilder->root('bigfoot_import');

        $rootNode
            ->children()
                ->arrayNode('nb_ligne_par_lot')
                    ->prototype('array')
                    ->useAttributeAsKey('name')
                    ->prototype('variable')->end()
                    ->end()
                ->end()
                ->scalarNode('max_execution_time')->defaultValue('30')->end()
            ->end();

        return $treeBuilder;
    }
}
