<?php declare(strict_types=1);
namespace Nevay\OTelSDK\Configuration\Config\Metrics;

use Amp\Http\Client\Connection\DefaultConnectionFactory;
use Amp\Http\Client\Connection\UnlimitedConnectionPool;
use Amp\Http\Client\HttpClientBuilder;
use Amp\Socket\Certificate;
use Amp\Socket\ClientTlsContext;
use Amp\Socket\ConnectContext;
use League\Uri;
use Nevay\OTelSDK\Configuration\Internal\Util;
use Nevay\OTelSDK\Metrics\Aggregation\Base2ExponentialBucketHistogramAggregation;
use Nevay\OTelSDK\Metrics\Aggregation\DefaultAggregation;
use Nevay\OTelSDK\Metrics\Aggregation\ExplicitBucketHistogramAggregation;
use Nevay\OTelSDK\Metrics\InstrumentType;
use Nevay\OTelSDK\Metrics\MetricExporter;
use Nevay\OTelSDK\Otlp\OtlpHttpMetricExporter;
use Nevay\OTelSDK\Otlp\ProtobufFormat;
use Nevay\OTelSDK\Otlp\OltpTemporality;
use Nevay\SPI\ServiceProviderDependency\PackageDependency;
use OpenTelemetry\API\Configuration\Config\ComponentProvider;
use OpenTelemetry\API\Configuration\Config\ComponentProviderRegistry;
use OpenTelemetry\API\Configuration\Context;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\Definition\Builder\NodeBuilder;

/**
 * @implements ComponentProvider<MetricExporter>
 */
#[PackageDependency('tbachert/otel-sdk-otlpexporter', '^0.1')]
#[PackageDependency('amphp/http-client', '^5.0')]
#[PackageDependency('amphp/socket', '^2.0')]
#[PackageDependency('league/uri', '^7.0')]
final class MetricExporterOtlp implements ComponentProvider {

    /**
     * @param array{
     *     endpoint: string,
     *     certificate_file: ?string,
     *     client_key_file: ?string,
     *     client_certificate_file: ?string,
     *     headers: list<array{
     *         name: string,
     *         value: string,
     *     }>,
     *     headers_list: ?string,
     *     compression: 'gzip'|null,
     *     timeout: int<0, max>,
     *     encoding: 'protobuf'|'json',
     *     temporality_preference: 'cumulative'|'delta'|'lowmemory',
     *     default_histogram_aggregation: 'explicit_bucket_histogram'|'base2_exponential_bucket_histogram',
     * } $properties
     */
    public function createPlugin(array $properties, Context $context): MetricExporter {
        $tlsContext = new ClientTlsContext();
        if ($clientCertificate = $properties['client_certificate_file']) {
            $tlsContext = $tlsContext->withCertificate(new Certificate($clientCertificate, $properties['client_key_file']));
        }
        if ($certificate = $properties['certificate_file']) {
            $tlsContext = $tlsContext->withCaPath($certificate);
        }

        $client = (new HttpClientBuilder())
            ->retry(0)
            ->usingPool(new UnlimitedConnectionPool(new DefaultConnectionFactory(connectContext: (new ConnectContext())->withTlsContext($tlsContext))))
            ->build();

        return new OtlpHttpMetricExporter(
            client: $client,
            endpoint: Uri\Http::new($properties['endpoint']),
            format: match ($properties['encoding']) {
                'protobuf' => ProtobufFormat::Protobuf,
                'json' => ProtobufFormat::Json,
            },
            compression: $properties['compression'],
            headers: Util::parseMapList($properties['headers'], $properties['headers_list']),
            timeout: $properties['timeout'] / 1e3,
            temporalityResolver: match ($properties['temporality_preference']) {
                'cumulative' => OltpTemporality::Cumulative,
                'delta' => OltpTemporality::Delta,
                'lowmemory' => OltpTemporality::LowMemory,
            },
            aggregation: (new DefaultAggregation())->with(InstrumentType::Histogram, match ($properties['default_histogram_aggregation']) {
                'explicit_bucket_histogram' => new ExplicitBucketHistogramAggregation(),
                'base2_exponential_bucket_histogram' => new Base2ExponentialBucketHistogramAggregation(),
            }),
            meterProvider: $context->meterProvider,
            logger: $context->logger,
        );
    }

    public function getConfig(ComponentProviderRegistry $registry, NodeBuilder $builder): ArrayNodeDefinition {
        $node = $builder->arrayNode('otlp_http');
        $node
            ->children()
                ->scalarNode('endpoint')->defaultValue('http://localhost:4318/v1/metrics')->validate()->always(Util::ensureString())->end()->end()
                ->scalarNode('certificate_file')->defaultNull()->validate()->always(Util::ensurePath())->end()->end()
                ->scalarNode('client_key_file')->defaultNull()->validate()->always(Util::ensurePath())->end()->end()
                ->scalarNode('client_certificate_file')->defaultNull()->validate()->always(Util::ensurePath())->end()->end()
                ->arrayNode('headers')
                    ->arrayPrototype()
                        ->children()
                            ->scalarNode('name')->isRequired()->validate()->always(Util::ensureString())->end()->end()
                            ->scalarNode('value')->isRequired()->validate()->always(Util::ensureString())->end()->end()
                        ->end()
                    ->end()
                ->end()
                ->scalarNode('headers_list')->defaultNull()->validate()->always(Util::ensureString())->end()->end()
                ->enumNode('compression')->values(['gzip'])->defaultNull()->validate()->always(Util::ensureString())->end()->end()
                ->integerNode('timeout')->min(0)->defaultValue(10000)->end()
                ->enumNode('encoding')
                    ->values(['protobuf', 'json'])
                    ->defaultValue('protobuf')
                ->end()
                ->enumNode('temporality_preference')
                    ->values(['cumulative', 'delta', 'lowmemory'])
                    ->defaultValue('cumulative')
                ->end()
                ->enumNode('default_histogram_aggregation')
                    ->values(['explicit_bucket_histogram', 'base2_exponential_bucket_histogram'])
                    ->defaultValue('explicit_bucket_histogram')
                ->end()
            ->end()
        ;

        return $node;
    }
}
