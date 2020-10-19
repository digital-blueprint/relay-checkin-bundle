<?php

declare(strict_types=1);

namespace DBP\API\LocationCheckInBundle\DependencyInjection;

use Symfony\Component\Cache\Adapter\FilesystemAdapter;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Symfony\Component\HttpKernel\DependencyInjection\ConfigurableExtension;

class DbpLocationCheckInExtension extends ConfigurableExtension
{
    public function loadInternal(array $mergedConfig, ContainerBuilder $container)
    {
        $pathsToHide = [
            '/location_check_in_actions/{id}',
            '/location_guest_check_in_actions',
            '/location_guest_check_in_actions/{id}',
            '/location_check_out_actions',
            '/location_check_out_actions/{id}',
        ];

        $this->extendArrayParameter($container, 'dbp_api.paths_to_hide', $pathsToHide);

        $this->extendArrayParameter(
            $container, 'api_platform.resource_class_directories', [__DIR__.'/../Entity']);

        $def = $container->register('dbp_api.cache.location_check_in.location', FilesystemAdapter::class);
        $def->setArguments(['location-check-in', 60, '%kernel.cache_dir%/dbp/location-check-in']);
        $def->setPublic(true);
        $def->addTag('cache.pool');

        $loader = new YamlFileLoader(
            $container,
            new FileLocator(__DIR__.'/../Resources/config')
        );

        $loader->load('services.yaml');

        $container->setParameter('dbp_api.location_check_in.config', $mergedConfig);
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
