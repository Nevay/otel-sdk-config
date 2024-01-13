<?php declare(strict_types=1);
namespace Nevay\OtelSDK\Configuration\Env\EnvLoaders\Metrics;

use Amp\Dns;
use Amp\Http\Server\SocketHttpServer;
use Amp\Socket\InternetAddress;
use Nevay\OtelSDK\Configuration\Context;
use Nevay\OtelSDK\Configuration\Env\EnvResolver;
use Nevay\OtelSDK\Configuration\Env\Loader;
use Nevay\OtelSDK\Configuration\Env\LoaderRegistry;
use Nevay\OtelSDK\Metrics\MetricReader;
use Nevay\OtelSDK\Metrics\MetricReader\PullMetricReader;
use Nevay\OtelSDK\Prometheus\PrometheusMetricExporter;
use Nevay\SPI\ServiceProviderDependency\PackageDependency;
use Psr\Log\NullLogger;

/**
 * @implements Loader<MetricReader>
 */
#[PackageDependency('tbachert/otel-sdk-prometheusexporter', '^0.1')]
#[PackageDependency('amphp/http-server', '^3.0')]
#[PackageDependency('amphp/socket', '^2.0')]
#[PackageDependency('amphp/dns', '^2.0')]
final class MetricReaderLoaderPrometheus implements Loader {

    public function load(EnvResolver $env, LoaderRegistry $registry, Context $context): MetricReader {
        $server = SocketHttpServer::createForDirectAccess($context->logger ?? new NullLogger(), allowedMethods: ['GET']);

        $host = $env->string('OTEL_EXPORTER_PROMETHEUS_HOST') ?? 'localhost';
        $port = $env->numeric('OTEL_EXPORTER_PROMETHEUS_PORT') ?? 9464;

        $address = Dns\resolve($host)[0]->getValue();
        $server->expose(new InternetAddress(
            address: $address,
            port: $port,
        ));

        return new PullMetricReader(
            metricExporter: new PrometheusMetricExporter($server),
            exportTimeoutMillis: $env->numeric('OTEL_METRIC_EXPORT_TIMEOUT') ?? 30000,
            meterProvider: $context->meterProvider,
        );
    }

    public function name(): string {
        return 'prometheus';
    }
}
