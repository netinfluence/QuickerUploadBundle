<?php

namespace Netinfluence\QuickerUploadBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * Class Configuration
 */
class Configuration implements ConfigurationInterface
{
    /**
     * {@inheritdoc}
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();

        $rootNode = $treeBuilder->root('netinfluence_quicker_upload');

        $rootNode
            ->children()
                ->arrayNode('validation')
                    ->children()
                        ->arrayNode('image')
                            ->children()
                                ->arrayNode('NotNull')->end()
                                // We want to have by default at least an "Image" constraint
                                ->arrayNode('Image')
                                    ->children()
                                        ->scalarNode('maxSize')
                                            // This looks like a sensible default for most users
                                            ->defaultValue('10M')
                                        ->end()
                                        // We set some defaults corresponding to a standard GD install - else ImagineBundle will fail
                                        ->arrayNode('mimeTypes')
                                            ->defaultValue(array('image/gif', 'image/jpg', 'image/jpeg', 'image/png', 'image/bmp', 'image/x-windows-bmp'))
                                        ->end()
                                        ->ignoreExtraKeys()
                                    ->end()
                                ->end()
                                // User can add any other constraints
                                ->ignoreExtraKeys()
                            ->end()
                        ->end()
                    ->end()
                ->end()
                ->arrayNode('filesystems')
                    ->children()
                        ->scalarNode('sandbox')
                            ->isRequired()
                            ->cannotbeEmpty()
                            ->info('Name of the Gaufrette filesystem to use, such as "gaufrette.sandbox_filesystem"')
                        ->end()
                    ->end()
                ->end()
            ->end()
        ;

        return $treeBuilder;
    }
}