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
use Nevay\OTelSDK\Otlp\OtlpHttpSpanExporter;
use Nevay\OTelSDK\Otlp\ProtobufFormat;
use Nevay\OTelSDK\Trace\SpanExporter;
use Nevay\SPI\ServiceProviderDependency\PackageDependency;
use OpenTelemetry\API\Configuration\Config\ComponentProvider;
use OpenTelemetry\API\Configuration\Config\ComponentProviderRegistry;
use OpenTelemetry\API\Configuration\Context;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\Definition\Builder\NodeBuilder;

/**
 * @implements ComponentProvider<SpanExporter>
 */
#[PackageDependency('tbachert/otel-sdk-otlpexporter', '^0.1')]
#[PackageDependency('amphp/http-client', '^5.0')]
#[PackageDependency('amphp/socket', '^2.0')]
#[PackageDependency('league/uri', '^7.0')]
final class SpanExporterOtlp implements ComponentProvider {

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
     * } $properties
     */
    public function createPlugin(array $properties, Context $context): SpanExporter {
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

        return new OtlpHttpSpanExporter(
            client: $client,
            endpoint: Uri\Http::new($properties['endpoint']),
            format: match ($properties['encoding']) {
                'protobuf' => ProtobufFormat::Protobuf,
                'json' => ProtobufFormat::Json,
            },
            compression: $properties['compression'],
            headers: Util::parseMapList($properties['headers'], $properties['headers_list']),
            timeout: $properties['timeout'] / 1e3,
            meterProvider: $context->meterProvider,
            logger: $context->logger,
        );
    }

    public function getConfig(ComponentProviderRegistry $registry, NodeBuilder $builder): ArrayNodeDefinition {
        $node = $builder->arrayNode('otlp_http');
        $node
            ->children()
                ->scalarNode('endpoint')->defaultValue('http://localhost:4318/v1/traces')->validate()->always(Util::ensureString())->end()->end()
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
                ->enumNode('compression')->values(['gzip'])->defaultNull()->end()
                ->integerNode('timeout')->min(0)->defaultValue(10000)->end()
                ->enumNode('encoding')
                    ->values(['protobuf', 'json'])
                    ->defaultValue('protobuf')
                ->end()
            ->end()
        ;

        return $node;
    }
}
