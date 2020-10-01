<?php

declare(strict_types=1);

namespace DBP\API\LocationCheckInBundle\DependencyInjection;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;

class DbpLocationCheckInExtension extends Extension
{
    public function load(array $configs, ContainerBuilder $container)
    {
        $pathsToHide = [
            '/location_check_in_actions',
            '/location_check_in_actions/{id}',
        ];

        $this->extendArrayParameter($container, 'dbp_api.paths_to_hide', $pathsToHide);

        $this->extendArrayParameter(
            $container, 'api_platform.resource_class_directories', [__DIR__.'/../Entity']);

        $loader = new YamlFileLoader(
            $container,
            new FileLocator(__DIR__.'/../Resources/config')
        );

        // TODO: To get rid of error message 'Environment variables "CAMPUS_QR_URL" are never used.'
        $container->setParameter('campus_qr_url',$configs[0]['campus_qr_url']);

        $loader->load('services.yaml');
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
