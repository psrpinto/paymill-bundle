<?php

namespace Memeoirs\PaymillBundle\DependencyInjection;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use Symfony\Component\DependencyInjection\Loader;

class MemeoirsPaymillExtension extends Extension
{
    /**
     * {@inheritDoc}
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        $loader = new Loader\YamlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));
        $loader->load('services.yml');

        $container->setParameter('memeoirs_paymill.api_private_key', $config['api_private_key']);
        $container->setParameter('memeoirs_paymill.api_public_key', $config['api_public_key']);
        $container->setParameter('memeoirs_paymill.initialize_template', $config['initialize_template']);
    }
}
