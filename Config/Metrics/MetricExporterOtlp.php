<?php declare(strict_types=1);
namespace Nevay\OtelSDK\Configuration\Config\Metrics;

use Amp\Http\Client\Connection\DefaultConnectionFactory;
use Amp\Http\Client\Connection\UnlimitedConnectionPool;
use Amp\Http\Client\HttpClientBuilder;
use Amp\Socket\Certificate;
use Amp\Socket\ClientTlsContext;
use Amp\Socket\ConnectContext;
use League\Uri;
use Nevay\OtelSDK\Configuration\Config\ComponentProvider;
use Nevay\OtelSDK\Configuration\Config\ComponentProviderDependency;
use Nevay\OtelSDK\Configuration\Config\ComponentProviderRegistry;
use Nevay\OtelSDK\Configuration\Context;
use Nevay\OtelSDK\Metrics\AggregationResolvers;
use Nevay\OtelSDK\Metrics\MetricExporter;
use Nevay\OtelSDK\Metrics\TemporalityResolvers;
use Nevay\OtelSDK\Otlp\OtlpHttpMetricExporter;
use Nevay\OtelSDK\Otlp\ProtobufFormat;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;

#[ComponentProviderDependency('tbachert/otel-sdk-otlpexporter', '^0.1')]
#[ComponentProviderDependency('amphp/http-client', '^5.0')]
#[ComponentProviderDependency('amphp/socket', '^2.0')]
#[ComponentProviderDependency('league/uri', '^7.0')]
final class MetricExporterOtlp implements ComponentProvider {

    /**
     * @param array{
     *     protocol: 'http/protobuf'|'http/json',
     *     endpoint: string,
     *     certificate: ?string,
     *     client_key: ?string,
     *     client_certificate: ?string,
     *     headers: array<string, string>,
     *     compression: 'gzip'|null,
     *     timeout: int<0, max>,
     *     temporality_preference: 'cumulative'|'delta'|'lowmemory',
     *     default_histogram_aggregation: 'explicit_bucket_histogram',
     * } $properties
     */
    public function createPlugin(array $properties, Context $context): MetricExporter {
        $tlsContext = new ClientTlsContext();
        if ($clientCertificate = $properties['client_certificate']) {
            $tlsContext = $tlsContext->withCertificate(new Certificate($clientCertificate, $properties['client_key']));
        }
        if ($certificate = $properties['certificate']) {
            $tlsContext = $tlsContext->withCaPath($certificate);
        }

        $client = (new HttpClientBuilder())
            ->retry(0)
            ->usingPool(new UnlimitedConnectionPool(new DefaultConnectionFactory(connectContext: (new ConnectContext())->withTlsContext($tlsContext))))
            ->build();

        return new OtlpHttpMetricExporter(
            client: $client,
            endpoint: Uri\Http::new($properties['endpoint'] . '/v1/metrics'),
            format: match ($properties['protocol']) {
                'http/protobuf' => ProtobufFormat::PROTOBUF,
                'http/json' => ProtobufFormat::JSON,
            },
            compression: $properties['compression'],
            headers: $properties['headers'],
            timeout: $properties['timeout'],
            temporalityResolver: match ($properties['temporality_preference']) {
                'cumulative' => TemporalityResolvers::Cumulative,
                'delta' => TemporalityResolvers::Delta,
                'lowmemory' => TemporalityResolvers::LowMemory,
            },
            aggregationResolver: match ($properties['default_histogram_aggregation']) {
                'explicit_bucket_histogram' => AggregationResolvers::Default,
            },
            logger: $context->logger,
        );
    }

    public function getConfig(ComponentProviderRegistry $registry): ArrayNodeDefinition {
        $node = new ArrayNodeDefinition('otlp');
        $node
            ->children()
                ->enumNode('protocol')->isRequired()->values(['http/protobuf', 'http/json'])->end()
                ->scalarNode('endpoint')->isRequired()->end()
                ->scalarNode('certificate')->defaultNull()->end()
                ->scalarNode('client_key')->defaultNull()->end()
                ->scalarNode('client_certificate')->defaultNull()->end()
                ->arrayNode('headers')
                    ->scalarPrototype()->end()
                ->end()
                ->enumNode('compression')->values(['gzip'])->defaultNull()->end()
                ->integerNode('timeout')->min(0)->defaultValue(10)->end()
                ->enumNode('temporality_preference')
                    ->values(['cumulative', 'delta', 'lowmemory'])
                    ->defaultValue('cumulative')
                ->end()
                ->enumNode('default_histogram_aggregation')
                    ->values(['explicit_bucket_histogram'])
                    ->defaultValue('explicit_bucket_histogram')
                ->end()
            ->end()
        ;

        return $node;
    }
}
