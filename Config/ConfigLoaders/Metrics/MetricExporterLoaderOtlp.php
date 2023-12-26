<?php declare(strict_types=1);
namespace Nevay\OtelSDK\Configuration\Config\ConfigLoaders\Metrics;

use Amp\Http\Client\Connection\DefaultConnectionFactory;
use Amp\Http\Client\Connection\UnlimitedConnectionPool;
use Amp\Http\Client\HttpClientBuilder;
use Amp\Socket\Certificate;
use Amp\Socket\ClientTlsContext;
use Amp\Socket\ConnectContext;
use League\Uri;
use Nevay\OtelSDK\Configuration\Config\Loader;
use Nevay\OtelSDK\Configuration\Config\LoaderRegistry;
use Nevay\OtelSDK\Configuration\Context;
use Nevay\OtelSDK\Metrics\MetricExporter;
use Nevay\OtelSDK\Otlp\OtlpHttpMetricExporter;
use Nevay\OtelSDK\Otlp\ProtobufFormat;

/**
 * @implements Loader<MetricExporter>
 */
final class MetricExporterLoaderOtlp implements Loader {

    public function load(array $config, LoaderRegistry $registry, Context $context): MetricExporter {
        $tlsContext = new ClientTlsContext();
        if ($clientCertificate = $config['client_certificate'] ?? null) {
            $tlsContext = $tlsContext->withCertificate(new Certificate($clientCertificate, $config['client_key'] ?? null));
        }
        if ($certificate = $config['certificate'] ?? null) {
            $tlsContext = $tlsContext->withCaPath($certificate);
        }

        $client = (new HttpClientBuilder())
            ->retry(0)
            ->usingPool(new UnlimitedConnectionPool(new DefaultConnectionFactory(connectContext: (new ConnectContext())->withTlsContext($tlsContext))))
            ->build();

        return new OtlpHttpMetricExporter(
            client: $client,
            endpoint: Uri\Http::new($config['endpoint'] . '/v1/metrics'),
            format: match ($config['protocol']) {
                'http/protobuf' => ProtobufFormat::PROTOBUF,
                'http/json' => ProtobufFormat::JSON,
            },
            compression: $config['compression'] ?? null,
            headers: $config['headers'] ?? [],
            timeout: $config['timeout'] ?? 10.,
            logger: $context->logger,
        );
    }

    public function type(): string {
        return MetricExporter::class;
    }

    public function name(): string {
        return 'otlp';
    }

    public function dependencies(): array {
        return [
            'tbachert/otel-sdk-otlpexporter' => '^0.1',
            'amphp/http-client' => '^5.0',
            'amphp/socket' => '^2.0',
            'league/uri' => '^7',
        ];
    }
}
