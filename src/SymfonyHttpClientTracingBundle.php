<?php
declare(strict_types=1);

namespace Zim\SymfonyHttpClientTracingBundle;

use Symfony\Component\Config\Definition\Configurator\DefinitionConfigurator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\HttpKernel\Bundle\AbstractBundle;
use Zim\SymfonyHttpClientTracingBundle\DependencyInjection\DecorateHttpClientPass;

class SymfonyHttpClientTracingBundle extends AbstractBundle
{
    protected string $extensionAlias = 'http_client_tracing';

    public function configure(DefinitionConfigurator $definition): void
    {
        $definition->rootNode()
            ->children()
                ->arrayNode('decorated_services')
                    ->arrayPrototype()
                        ->children()
                            ->scalarNode('service')
                                ->isRequired()
                            ->end()
                        ->end()
                        ->children()
                            ->booleanNode('propagate')
                                ->defaultValue(false)
                                ->info('If true, trace data will be propagated when making requests')
                            ->end()
                        ->end()
                        ->children()
                            ->booleanNode('eager_content')
                                ->defaultValue(true)
                                ->info('If true, content will be downloaded before returning the response. This will track time accurately but will buffer the whole response in memory')
                            ->end()
                        ->end()
                    ->end()
                ->end()
            ->end()
        ;
    }

    public function loadExtension(array $config, ContainerConfigurator $container, ContainerBuilder $builder): void
    {
        $builder->setParameter('tracing.http_client.decorated_services', $config['decorated_services']);
    }

    public function build(ContainerBuilder $container)
    {
        $container->addCompilerPass(new DecorateHttpClientPass());
    }
}
