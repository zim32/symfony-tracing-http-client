<?php
declare(strict_types=1);

namespace Zim\SymfonyHttpClientTracingBundle\Instrumentation\HttpClient;

use OpenTelemetry\API\Trace\Propagation\TraceContextPropagator;
use OpenTelemetry\API\Trace\SpanKind;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;
use Symfony\Contracts\HttpClient\ResponseStreamInterface;
use Zim\SymfonyTracingCoreBundle\ScopedTracerInterface;

class InstrumentedHttpClient implements HttpClientInterface
{
    public function __construct(
        private readonly HttpClientInterface $inner,
        private readonly ScopedTracerInterface $httpTracer,
        private readonly bool $propagate,
        private readonly bool $eagerContent,
    )
    {
    }

    public function request(string $method, string $url, array $options = []): ResponseInterface
    {
        $spanName = sprintf('Outgoing %s %s', $method, $url);
        $span = $this->httpTracer->startSpan($spanName, SpanKind::KIND_CLIENT);

        if ($this->propagate) {
            $carrier = [];
            $headers = $options['headers'] ?? [];
            TraceContextPropagator::getInstance()->inject($carrier);
            foreach ($carrier as $key => $value) {
                $headers[$key] = $value;
            }
            $options['headers'] = $headers;
        }

        try {
            $result = $this->inner->request($method, $url, $options);

            if ($this->eagerContent) {
                // this will download content, to be able to trace time
                $result->getContent();
            }

            return $result;
        } finally {
            $span->end();
        }
    }

    public function stream(iterable|ResponseInterface $responses, ?float $timeout = null): ResponseStreamInterface
    {
        return $this->inner->stream($responses, $timeout);
    }

    public function withOptions(array $options): static
    {
        return new self(
            inner: $this->inner->withOptions($options),
            httpTracer: $this->httpTracer,
        );
    }
}
