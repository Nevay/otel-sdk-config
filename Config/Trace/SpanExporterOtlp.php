<?php declare(strict_types=1);
namespace Nevay\OtelSDK\Configuration\Config\Trace;

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
use Nevay\OtelSDK\Otlp\OtlpHttpSpanExporter;
use Nevay\OtelSDK\Otlp\ProtobufFormat;
use Nevay\OtelSDK\Trace\SpanExporter;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;

#[ComponentProviderDependency('tbachert/otel-sdk-otlpexporter', '^0.1')]
#[ComponentProviderDependency('amphp/http-client', '^5.0')]
#[ComponentProviderDependency('amphp/socket', '^2.0')]
#[ComponentProviderDependency('league/uri', '^7.0')]
final class SpanExporterOtlp implements ComponentProvider {

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
     * } $properties
     */
    public function createPlugin(array $properties, Context $context): SpanExporter {
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

        return new OtlpHttpSpanExporter(
            client: $client,
            endpoint: Uri\Http::new($properties['endpoint'] . '/v1/traces'),
            format: match ($properties['protocol']) {
                'http/protobuf' => ProtobufFormat::PROTOBUF,
                'http/json' => ProtobufFormat::JSON,
            },
            compression: $properties['compression'],
            headers: $properties['headers'],
            timeout: $properties['timeout'],
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
            ->end()
        ;

        return $node;
    }
}
