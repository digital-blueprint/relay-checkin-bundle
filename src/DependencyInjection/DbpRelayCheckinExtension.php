<?php

declare(strict_types=1);

namespace Dbp\Relay\CheckinBundle\DependencyInjection;

use Dbp\Relay\CheckinBundle\Message\GuestCheckOutMessage;
use Dbp\Relay\CoreBundle\Extension\ExtensionTrait;
use Symfony\Component\Cache\Adapter\FilesystemAdapter;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\PrependExtensionInterface;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Symfony\Component\HttpKernel\DependencyInjection\ConfigurableExtension;

class DbpRelayCheckinExtension extends ConfigurableExtension implements PrependExtensionInterface
{
    use ExtensionTrait;

    public function prepend(ContainerBuilder $container)
    {
        $this->addQueueMessage($container, GuestCheckOutMessage::class);
    }

    public function loadInternal(array $mergedConfig, ContainerBuilder $container)
    {
        $pathsToHide = [
            '/checkin/check-in-actions/{identifier}',
            '/checkin/guest-check-in-actions',
            '/checkin/guest-check-in-actions/{identifier}',
            '/checkin/check-out-actions',
            '/checkin/check-out-actions/{identifier}',
        ];

        foreach ($pathsToHide as $path) {
            $this->addPathToHide($container, $path);
        }

        $this->addResourceClassDirectory($container, __DIR__.'/../Entity');

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
}
