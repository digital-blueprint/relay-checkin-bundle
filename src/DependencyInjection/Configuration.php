<?php

declare(strict_types=1);

namespace Dbp\Relay\CheckinBundle\DependencyInjection;

use Dbp\Relay\CoreBundle\Authorization\AuthorizationConfigDefinition;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{
    public const ROLE_LOCATION_CHECK_IN = 'ROLE_LOCATION_CHECK_IN';
    public const ROLE_LOCATION_CHECK_IN_GUEST = 'ROLE_LOCATION_CHECK_IN_GUEST';

    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('dbp_relay_checkin');

        $treeBuilder->getRootNode()
            ->children()
            ->scalarNode('campus_qr_url')->end()
            ->scalarNode('campus_qr_token')->end()
            ->end()
            ->end()
        ;

        $treeBuilder->getRootNode()->append(
            AuthorizationConfigDefinition::create()
                ->addRole(self::ROLE_LOCATION_CHECK_IN, 'false', 'Returns true if the user is allowed to check in.')
                ->addRole(self::ROLE_LOCATION_CHECK_IN_GUEST, 'false', 'Returns true if the user is allowed to check in guests.')
                ->getNodeDefinition());

        return $treeBuilder;
    }
}
