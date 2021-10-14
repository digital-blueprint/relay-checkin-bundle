<?php

declare(strict_types=1);

namespace Dbp\Relay\CheckinBundle\DependencyInjection;

use Symfony\Component\Cache\Adapter\FilesystemAdapter;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\PrependExtensionInterface;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Symfony\Component\HttpKernel\DependencyInjection\ConfigurableExtension;

class DbpCheckinExtension extends ConfigurableExtension implements PrependExtensionInterface
{
    public function prepend(ContainerBuilder $container)
    {
        // https://symfony.com/doc/4.4/messenger.html#transports-async-queued-messages
        $this->extendArrayParameter($container, 'dbp_api.messenger_routing', [
            'Dbp\Relay\CheckinBundle\Message\GuestCheckOutMessage' => 'async',
        ]);
    }

    public function loadInternal(array $mergedConfig, ContainerBuilder $container)
    {
        $this->extendArrayParameter($container, 'dbp_api.paths_to_hide', [
            '/checkin/check_in_actions/{identifier}',
            '/checkin/guest_check_in_actions',
            '/checkin/guest_check_in_actions/{identifier}',
            '/checkin/check_out_actions',
            '/checkin/check_out_actions/{identifier}',
        ]);

        $this->extendArrayParameter(
            $container, 'api_platform.resource_class_directories', [__DIR__.'/../Entity']);

        $loader = new YamlFileLoader(
            $container,
            new FileLocator(__DIR__.'/../Resources/config')
        );
        $loader->load('services.yaml');

        $cacheDef = $container->register('dbp_api.cache.check_in.location', FilesystemAdapter::class);
        $cacheDef->setArguments(['check-in', 60, '%kernel.cache_dir%/dbp/check-in']);
        $cacheDef->setPublic(true);
        $cacheDef->addTag('cache.pool');

        $definition = $container->getDefinition('Dbp\Relay\CheckinBundle\Service\CheckinApi');
        $definition->addMethodCall('setConfig', [$mergedConfig]);
        $definition->addMethodCall('setCache', [$cacheDef]);
    }

    private function extendArrayParameter(ContainerBuilder $container, string $parameter, array $values)
    {
        if (!$container->hasParameter($parameter)) {
            $container->setParameter($parameter, []);
        }
        $oldValues = $container->getParameter($parameter);
        assert(is_array($oldValues));
        $container->setParameter($parameter, array_merge($oldValues, $values));
    }
}
