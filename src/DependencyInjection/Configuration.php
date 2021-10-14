<?php

declare(strict_types=1);

namespace Dbp\Relay\CheckinBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder('dbp_relay_checkin');

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
