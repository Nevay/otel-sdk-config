<?php declare(strict_types=1);
namespace Nevay\OTelSDK\Configuration\ConfigEnv\Metrics;

use Amp\Dns;
use Amp\Http\Server\DefaultErrorHandler;
use Amp\Http\Server\Driver\SocketClientFactory;
use Amp\Http\Server\SocketHttpServer;
use Amp\Socket\InternetAddress;
use Nevay\OTelSDK\Metrics\MetricReader;
use Nevay\OTelSDK\Metrics\MetricReader\PullMetricReader;
use Nevay\OTelSDK\Prometheus\Internal\HttpServer\HttpServerClosable;
use Nevay\OTelSDK\Prometheus\Internal\Socket\UnreferencedServerSocketFactory;
use Nevay\OTelSDK\Prometheus\PrometheusMetricExporter;
use Nevay\SPI\ServiceProviderDependency\PackageDependency;
use OpenTelemetry\API\Configuration\ConfigEnv\EnvComponentLoader;
use OpenTelemetry\API\Configuration\ConfigEnv\EnvComponentLoaderRegistry;
use OpenTelemetry\API\Configuration\ConfigEnv\EnvResolver;
use OpenTelemetry\API\Configuration\Context;

/**
 * @implements EnvComponentLoader<MetricReader>
 */
#[PackageDependency('tbachert/otel-sdk-prometheusexporter', '^0.1')]
#[PackageDependency('amphp/http-server', '^3.0')]
#[PackageDependency('amphp/socket', '^2.0')]
#[PackageDependency('amphp/dns', '^2.0')]
final class MetricReaderLoaderPrometheus implements EnvComponentLoader {

    public function load(EnvResolver $env, EnvComponentLoaderRegistry $registry, Context $context): MetricReader {
        $server = new SocketHttpServer(
            $context->logger,
            new UnreferencedServerSocketFactory(),
            new SocketClientFactory($context->logger),
            allowedMethods: ['GET'],
        );

        $host = $env->string('OTEL_EXPORTER_PROMETHEUS_HOST') ?? 'localhost';
        $port = $env->int('OTEL_EXPORTER_PROMETHEUS_PORT') ?? 9464;

        $address = Dns\resolve($host)[0]->getValue();
        $server->expose(new InternetAddress(
            address: $address,
            port: $port,
        ));

        $exporter = new PrometheusMetricExporter(new HttpServerClosable($server));
        $server->start($exporter, new DefaultErrorHandler());

        return new PullMetricReader(
            metricExporter: $exporter,
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
