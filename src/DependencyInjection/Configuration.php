<?php

/*
 * This file is part of Monsieur Biz' Search plugin for Sylius.
 *
 * (c) Monsieur Biz <sylius@monsieurbiz.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace MonsieurBiz\SyliusSearchPlugin\DependencyInjection;

use MonsieurBiz\SyliusSearchPlugin\Mapping\YamlMultipleFolderProvider;
use MonsieurBiz\SyliusSearchPlugin\Model\Datasource\RepositoryDatasource;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

final class Configuration implements ConfigurationInterface
{
    /**
     * {@inheritdoc}
     */
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('monsieur_biz_sylius_search');
        $rootNode = $treeBuilder->getRootNode();
        $rootNode
            ->children()
                ->arrayNode('documents')
                    ->useAttributeAsKey('code', false)
                    ->defaultValue([])
                    ->arrayPrototype()
                        ->children()
                            ->scalarNode('instant_search_enabled')->defaultValue(false)->end()
                            ->scalarNode('source')->isRequired()->cannotBeEmpty()->end()
                            ->scalarNode('target')->isRequired()->cannotBeEmpty()->end()
                            ->scalarNode('mapping_provider')->defaultValue(YamlMultipleFolderProvider::class)->end()
                            ->scalarNode('datasource')->defaultValue(RepositoryDatasource::class)->end()
                            ->arrayNode('templates')
                                ->addDefaultsIfNotSet()
                                ->children()
                                    ->scalarNode('item')->isRequired()->cannotBeEmpty()->end()
                                    ->scalarNode('instant')->isRequired()->cannotBeEmpty()->end()
                                ->end()
                            ->end()

                            // Limits
                            ->arrayNode('limits')
                                ->children()
                                    ->arrayNode('search')
                                        ->performNoDeepMerging()
                                        ->integerPrototype()->end()
                                        ->defaultValue([9, 18, 27])
                                    ->end()
                                    ->arrayNode('taxon')
                                        ->performNoDeepMerging()
                                        ->integerPrototype()->end()
                                        ->defaultValue([9, 18, 27])
                                    ->end()
                                    ->arrayNode('instant_search')
                                        ->performNoDeepMerging()
                                        ->integerPrototype()->end()
                                        ->defaultValue([10])
                                    ->end()
                                ->end()
                            ->end()
                        ->end()
                    ->end()
                ->end()
                ->arrayNode('automapper_classes')
                    ->children()
                        ->arrayNode('sources')
                            ->useAttributeAsKey('code', false)
                            ->defaultValue([])
                            ->prototype('scalar')->end()
                        ->end()
                        ->arrayNode('targets')
                            ->useAttributeAsKey('code', false)
                            ->defaultValue([])
                            ->prototype('scalar')->end()
                        ->end()
                    ->end()
                ->end()
                ->arrayNode('mapping_provider_directories')
                    ->defaultValue([])
                    ->prototype('scalar')->end()
                ->end()
            ->end()
        ;

        return $treeBuilder;
    }
}
