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
use Nevay\OTelSDK\Otlp\OtlpGrpcMetricExporter;
use Nevay\OTelSDK\Otlp\OtlpTemporality;
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
final class MetricExporterOtlpGrpc implements ComponentProvider {

    /**
     * @param array{
     *     endpoint: string,
     *     tls: array{
     *         ca_file: ?string,
     *         cert_file: ?string,
     *         key_file: ?string,
     *         insecure: bool,
     *     },
     *     headers: list<array{
     *         name: string,
     *         value: string,
     *     }>,
     *     headers_list: ?string,
     *     compression: 'gzip'|null,
     *     timeout: int<0, max>,
     *     temporality_preference: 'cumulative'|'delta'|'lowmemory',
     *     default_histogram_aggregation: 'explicit_bucket_histogram'|'base2_exponential_bucket_histogram',
     * } $properties
     */
    public function createPlugin(array $properties, Context $context): MetricExporter {
        $tlsContext = new ClientTlsContext();
        if ($clientCertificate = $properties['tls']['cert_file']) {
            $tlsContext = $tlsContext->withCertificate(new Certificate($clientCertificate, $properties['tls']['key_file']));
        }
        if ($certificate = $properties['tls']['ca_file']) {
            $tlsContext = $tlsContext->withCaPath($certificate);
        }

        $client = (new HttpClientBuilder())
            ->retry(0)
            ->usingPool(new UnlimitedConnectionPool(new DefaultConnectionFactory(connectContext: (new ConnectContext())->withTlsContext($tlsContext))))
            ->build();

        return new OtlpGrpcMetricExporter(
            client: $client,
            endpoint: Uri\Http::new($properties['endpoint']),
            compression: $properties['compression'],
            headers: Util::parseMapList($properties['headers'], $properties['headers_list']),
            timeout: $properties['timeout'] / 1e3,
            temporalityResolver: match ($properties['temporality_preference']) {
                'cumulative' => OtlpTemporality::Cumulative,
                'delta' => OtlpTemporality::Delta,
                'lowmemory' => OtlpTemporality::LowMemory,
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
        $node = $builder->arrayNode('otlp_grpc');
        $node
            ->children()
                ->scalarNode('endpoint')->defaultValue('http://localhost:4317')->validate()->always(Util::ensureString())->end()->end()
                ->arrayNode('tls')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->scalarNode('ca_file')->defaultNull()->validate()->always(Util::ensurePath())->end()->end()
                        ->scalarNode('cert_file')->defaultNull()->validate()->always(Util::ensurePath())->end()->end()
                        ->scalarNode('key_file')->defaultNull()->validate()->always(Util::ensurePath())->end()->end()
                        ->booleanNode('insecure')->defaultFalse()->end()
                    ->end()
                ->end()
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
