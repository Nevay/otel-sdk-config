<?php declare(strict_types=1);
namespace Nevay\OTelSDK\Configuration\Env\EnvLoaders\Metrics;

use Amp\Dns;
use Amp\Http\Server\SocketHttpServer;
use Amp\Socket\InternetAddress;
use Nevay\OTelSDK\Configuration\Context;
use Nevay\OTelSDK\Configuration\Env\EnvResolver;
use Nevay\OTelSDK\Configuration\Env\Loader;
use Nevay\OTelSDK\Configuration\Env\LoaderRegistry;
use Nevay\OTelSDK\Metrics\MetricReader;
use Nevay\OTelSDK\Metrics\MetricReader\PullMetricReader;
use Nevay\OTelSDK\Prometheus\PrometheusMetricExporter;
use Nevay\SPI\ServiceProviderDependency\PackageDependency;

/**
 * @implements Loader<MetricReader>
 */
#[PackageDependency('tbachert/otel-sdk-prometheusexporter', '^0.1')]
#[PackageDependency('amphp/http-server', '^3.0')]
#[PackageDependency('amphp/socket', '^2.0')]
#[PackageDependency('amphp/dns', '^2.0')]
final class MetricReaderLoaderPrometheus implements Loader {

    public function load(EnvResolver $env, LoaderRegistry $registry, Context $context): MetricReader {
        $server = SocketHttpServer::createForDirectAccess($context->logger, allowedMethods: ['GET']);

        $host = $env->string('OTEL_EXPORTER_PROMETHEUS_HOST') ?? 'localhost';
        $port = $env->int('OTEL_EXPORTER_PROMETHEUS_PORT') ?? 9464;

        $address = Dns\resolve($host)[0]->getValue();
        $server->expose(new InternetAddress(
            address: $address,
            port: $port,
        ));

        return new PullMetricReader(
            metricExporter: new PrometheusMetricExporter($server),
            exportTimeoutMillis: $env->int('OTEL_METRIC_EXPORT_TIMEOUT') ?? 30000,
            tracerProvider: $context->tracerProvider,
            meterProvider: $context->meterProvider,
            logger: $context->logger,
        );
    }

    public function name(): string {
        return 'prometheus';
    }
}
