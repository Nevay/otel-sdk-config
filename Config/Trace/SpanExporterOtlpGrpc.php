<?php declare(strict_types=1);
namespace Nevay\OTelSDK\Configuration\Config\Trace;

use Amp\Http\Client\Connection\DefaultConnectionFactory;
use Amp\Http\Client\Connection\UnlimitedConnectionPool;
use Amp\Http\Client\HttpClientBuilder;
use Amp\Socket\Certificate;
use Amp\Socket\ClientTlsContext;
use Amp\Socket\ConnectContext;
use League\Uri;
use Nevay\OTelSDK\Configuration\Internal\Util;
use Nevay\OTelSDK\Otlp\OtlpGrpcSpanExporter;
use Nevay\OTelSDK\Trace\SpanExporter;
use Nevay\SPI\ServiceProviderDependency\PackageDependency;
use OpenTelemetry\API\Configuration\Config\ComponentProvider;
use OpenTelemetry\API\Configuration\Config\ComponentProviderRegistry;
use OpenTelemetry\API\Configuration\Context;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\Definition\Builder\NodeBuilder;
use function parse_url;
use const PHP_URL_SCHEME;

/**
 * @implements ComponentProvider<SpanExporter>
 */
#[PackageDependency('tbachert/otel-sdk-otlpexporter', '^0.1')]
#[PackageDependency('amphp/http-client', '^5.0')]
#[PackageDependency('amphp/socket', '^2.0')]
#[PackageDependency('league/uri', '^7.0')]
final class SpanExporterOtlpGrpc implements ComponentProvider {

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
     * } $properties
     */
    public function createPlugin(array $properties, Context $context): SpanExporter {
        $tlsContext = new ClientTlsContext();
        if ($clientCertificate = $properties['tls']['cert_file']) {
            $tlsContext = $tlsContext->withCertificate(new Certificate($clientCertificate, $properties['tls']['key_file']));
        }
        if ($certificate = $properties['tls']['ca_file']) {
            $tlsContext = $tlsContext->withCaPath($certificate);
        }

        if (parse_url($properties['endpoint'], PHP_URL_SCHEME) === null) {
            $scheme = $properties['tls']['insecure']
                ? 'http'
                : 'https';

            $properties['endpoint'] = $scheme . '://' . $properties['endpoint'];
        }

        $client = (new HttpClientBuilder())
            ->retry(0)
            ->usingPool(new UnlimitedConnectionPool(new DefaultConnectionFactory(connectContext: (new ConnectContext())->withTlsContext($tlsContext))))
            ->build();

        return new OtlpGrpcSpanExporter(
            client: $client,
            endpoint: Uri\Http::new($properties['endpoint']),
            compression: $properties['compression'],
            headers: Util::parseMapList($properties['headers'], $properties['headers_list']),
            timeout: $properties['timeout'] / 1e3,
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
                ->enumNode('compression')->values(['gzip'])->defaultNull()->end()
                ->integerNode('timeout')->min(0)->defaultValue(10000)->end()
            ->end()
        ;

        return $node;
    }
}
