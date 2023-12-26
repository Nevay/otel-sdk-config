<?php declare(strict_types=1);
namespace Nevay\OtelSDK\Configuration\Config\ConfigLoaders\Metrics;

use Amp\Dns;
use Amp\Http\Server\SocketHttpServer;
use Amp\Socket\InternetAddress;
use Nevay\OtelSDK\Configuration\Config\Loader;
use Nevay\OtelSDK\Configuration\Config\LoaderRegistry;
use Nevay\OtelSDK\Configuration\Context;
use Nevay\OtelSDK\Metrics\MetricExporter;
use Nevay\OtelSDK\Prometheus\PrometheusMetricExporter;
use Psr\Log\NullLogger;

/**
 * @implements Loader<MetricExporter>
 */
final class MetricExporterLoaderPrometheus implements Loader {

    public function load(array $config, LoaderRegistry $registry, Context $context): MetricExporter {
        $server = SocketHttpServer::createForDirectAccess($context->logger ?? new NullLogger(), allowedMethods: ['GET']);

        $host = $config['host'] ?? 'localhost';
        $port = $config['port'] ?? 9464;

        $address = Dns\resolve($host)[0]->getValue();
        $server->expose(new InternetAddress(
            address: $address,
            port: $port,
        ));

        return new PrometheusMetricExporter(
            server: $server,
            withoutUnits: $config['without_units'] ?? false,
            withoutTypeSuffix: $config['without_type_suffix'] ?? false,
            withoutScopeInfo: $config['without_scope_info'] ?? false,
        );
    }

    public function type(): string {
        return MetricExporter::class;
    }

    public function name(): string {
        return 'prometheus';
    }

    public function dependencies(): array {
        return [
            'tbachert/otel-sdk-prometheusexporter' => '^0.1',
            'amphp/http-server' => '^3.0',
            'amphp/socket' => '^2.0',
            'amphp/dns' => '^2.0',
        ];
    }
}
