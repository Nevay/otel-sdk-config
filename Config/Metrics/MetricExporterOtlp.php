<?php declare(strict_types=1);
namespace Nevay\OTelSDK\Configuration\Config\Metrics;

use Amp\Http\Client\Connection\DefaultConnectionFactory;
use Amp\Http\Client\Connection\UnlimitedConnectionPool;
use Amp\Http\Client\HttpClientBuilder;
use Amp\Socket\Certificate;
use Amp\Socket\ClientTlsContext;
use Amp\Socket\ConnectContext;
use League\Uri;
use Nevay\OTelSDK\Configuration\ComponentProvider;
use Nevay\OTelSDK\Configuration\ComponentProviderRegistry;
use Nevay\OTelSDK\Configuration\Context;
use Nevay\OTelSDK\Configuration\Validation;
use Nevay\OTelSDK\Metrics\Aggregation\DefaultAggregation;
use Nevay\OTelSDK\Metrics\MetricExporter;
use Nevay\OTelSDK\Metrics\TemporalityResolvers;
use Nevay\OTelSDK\Otlp\OtlpHttpMetricExporter;
use Nevay\OTelSDK\Otlp\ProtobufFormat;
use Nevay\SPI\ServiceProviderDependency\PackageDependency;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;

#[PackageDependency('tbachert/otel-sdk-otlpexporter', '^0.1')]
#[PackageDependency('amphp/http-client', '^5.0')]
#[PackageDependency('amphp/socket', '^2.0')]
#[PackageDependency('league/uri', '^7.0')]
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
            aggregation: match ($properties['default_histogram_aggregation']) {
                'explicit_bucket_histogram' => new DefaultAggregation(),
            },
            logger: $context->logger,
        );
    }

    public function getConfig(ComponentProviderRegistry $registry): ArrayNodeDefinition {
        $node = new ArrayNodeDefinition('otlp');
        $node
            ->children()
                ->enumNode('protocol')->isRequired()->values(['http/protobuf', 'http/json'])->end()
                ->scalarNode('endpoint')->isRequired()->validate()->always(Validation::ensureString())->end()->end()
                ->scalarNode('certificate')->defaultNull()->validate()->always(Validation::ensureString())->end()->end()
                ->scalarNode('client_key')->defaultNull()->validate()->always(Validation::ensureString())->end()->end()
                ->scalarNode('client_certificate')->defaultNull()->validate()->always(Validation::ensureString())->end()->end()
                ->arrayNode('headers')
                    ->scalarPrototype()->end()
                ->end()
                ->enumNode('compression')->values(['gzip'])->defaultNull()->validate()->always(Validation::ensureString())->end()->end()
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
