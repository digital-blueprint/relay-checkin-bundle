<?php

declare(strict_types=1);

namespace DBP\API\LocationCheckInBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder('dbp_location_check_in');

        $treeBuilder->getRootNode()
            ->children()
            ->scalarNode('campus_qr_url')->end()
            ->scalarNode('campus_qr_token')->end()
            ->end()
            ->end()
        ;

        return $treeBuilder;
    }
}
