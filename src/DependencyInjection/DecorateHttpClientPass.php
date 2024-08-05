<?php
declare(strict_types=1);

namespace Zim\SymfonyHttpClientTracingBundle\DependencyInjection;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;
use Zim\SymfonyHttpClientTracingBundle\Instrumentation\HttpClient\InstrumentedHttpClient;

class DecorateHttpClientPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container)
    {
        $decoratedServices = $container->getParameter('tracing.http_client.decorated_services');

        foreach ($decoratedServices as $idx => $params) {
            $decorated = (new Definition(InstrumentedHttpClient::class))
                ->setDecoratedService($params['service'])
            ;

            $decorated->setArguments([
                '$inner' => new Reference('.inner'),
                '$httpTracer' => new Reference('tracing.scoped_tracer.http'),
                '$propagate' => $params['propagate'],
                '$eagerContent' => $params['eager_content'],
            ]);

            $container->setDefinition("tracing.instrumented_http_client.$idx", $decorated);
        }

        $container->getParameterBag()->remove('tracing.http_client.decorated_clients');
    }
}
