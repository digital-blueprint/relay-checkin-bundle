<?php

declare(strict_types=1);

namespace Dbp\Relay\CheckinBundle\DependencyInjection;

use Dbp\Relay\CheckinBundle\Message\GuestCheckOutMessage;
use Dbp\Relay\CoreBundle\Extension\ExtensionTrait;
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
        $this->addQueueMessageClass($container, GuestCheckOutMessage::class);
    }

    public function loadInternal(array $mergedConfig, ContainerBuilder $container)
    {
        $loader = new YamlFileLoader(
            $container,
            new FileLocator(__DIR__.'/../Resources/config')
        );
        $loader->load('services.yaml');

        $definition = $container->getDefinition('Dbp\Relay\CheckinBundle\Service\CheckinApi');
        $definition->addMethodCall('setConfig', [$mergedConfig]);
    }
}
