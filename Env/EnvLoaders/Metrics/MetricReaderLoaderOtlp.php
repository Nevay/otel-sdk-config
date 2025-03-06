<?php declare(strict_types=1);
namespace Nevay\OTelSDK\Configuration\Env\EnvLoaders\Metrics;

use Amp\Http\Client\Connection\DefaultConnectionFactory;
use Amp\Http\Client\Connection\UnlimitedConnectionPool;
use Amp\Http\Client\HttpClientBuilder;
use Amp\Socket\Certificate;
use Amp\Socket\ClientTlsContext;
use Amp\Socket\ConnectContext;
use League\Uri;
use Nevay\OTelSDK\Configuration\Context;
use Nevay\OTelSDK\Configuration\Env\EnvResolver;
use Nevay\OTelSDK\Configuration\Env\Loader;
use Nevay\OTelSDK\Configuration\Env\LoaderRegistry;
use Nevay\OTelSDK\Metrics\Aggregation\Base2ExponentialBucketHistogramAggregation;
use Nevay\OTelSDK\Metrics\Aggregation\DefaultAggregation;
use Nevay\OTelSDK\Metrics\Aggregation\ExplicitBucketHistogramAggregation;
use Nevay\OTelSDK\Metrics\InstrumentType;
use Nevay\OTelSDK\Metrics\MetricReader;
use Nevay\OTelSDK\Metrics\MetricReader\PeriodicExportingMetricReader;
use Nevay\OTelSDK\Metrics\TemporalityResolvers;
use Nevay\OTelSDK\Otlp\OtlpHttpMetricExporter;
use Nevay\OTelSDK\Otlp\ProtobufFormat;
use Nevay\SPI\ServiceProviderDependency\PackageDependency;
use function strtolower;

/**
 * @implements Loader<MetricReader>
 */
#[PackageDependency('tbachert/otel-sdk-otlpexporter', '^0.1')]
#[PackageDependency('amphp/http-client', '^5.0')]
#[PackageDependency('amphp/socket', '^2.0')]
#[PackageDependency('league/uri', '^7.0')]
final class MetricReaderLoaderOtlp implements Loader {

    public function load(EnvResolver $env, LoaderRegistry $registry, Context $context): MetricReader {
        $tlsContext = new ClientTlsContext();
        if ($clientCertificate = $env->path('OTEL_EXPORTER_OTLP_METRICS_CLIENT_CERTIFICATE') ?? $env->path('OTEL_EXPORTER_OTLP_CLIENT_CERTIFICATE')) {
            $tlsContext = $tlsContext->withCertificate(new Certificate($clientCertificate, $env->path('OTEL_EXPORTER_OTLP_METRICS_CLIENT_KEY') ?? $env->path('OTEL_EXPORTER_OTLP_CLIENT_KEY')));
        }
        if ($certificate = $env->path('OTEL_EXPORTER_OTLP_METRICS_CERTIFICATE') ?? $env->path('OTEL_EXPORTER_OTLP_CERTIFICATE')) {
            $tlsContext = $tlsContext->withCaPath($certificate);
        }

        $client = (new HttpClientBuilder())
            ->retry(0)
            ->usingPool(new UnlimitedConnectionPool(new DefaultConnectionFactory(connectContext: (new ConnectContext())->withTlsContext($tlsContext))))
            ->build();

        return new PeriodicExportingMetricReader(
            metricExporter: new OtlpHttpMetricExporter(
                client: $client,
                endpoint: Uri\Http::new($env->string('OTEL_EXPORTER_OTLP_METRICS_ENDPOINT') ?? ($env->string('OTEL_EXPORTER_OTLP_ENDPOINT') ?? 'http://localhost:4318') . '/v1/metrics'),
                format: match ($env->string('OTEL_EXPORTER_OTLP_METRICS_PROTOCOL') ?? $env->string('OTEL_EXPORTER_OTLP_PROTOCOL') ?? 'http/protobuf') {
                    'http/protobuf' => ProtobufFormat::Protobuf,
                    'http/json' => ProtobufFormat::Json,
                },
                compression: $env->string('OTEL_EXPORTER_OTLP_METRICS_COMPRESSION') ?? $env->string('OTEL_EXPORTER_OTLP_COMPRESSION'),
                headers: $env->map('OTEL_EXPORTER_OTLP_METRICS_HEADERS') ?? $env->map('OTEL_EXPORTER_OTLP_HEADERS') ?? [],
                timeout: ($env->int('OTEL_EXPORTER_OTLP_METRICS_TIMEOUT') ?? $env->int('OTEL_EXPORTER_OTLP_TIMEOUT') ?? 10000) / 1e3,
                temporalityResolver: match (strtolower($env->string('OTEL_EXPORTER_OTLP_METRICS_TEMPORALITY_PREFERENCE') ?? 'cumulative')) {
                    'cumulative' => TemporalityResolvers::Cumulative,
                    'delta' => TemporalityResolvers::Delta,
                    'lowmemory' => TemporalityResolvers::LowMemory,
                },
                aggregation: (new DefaultAggregation())->with(InstrumentType::Histogram, match ($env->string('OTEL_EXPORTER_OTLP_METRICS_DEFAULT_HISTOGRAM_AGGREGATION') ?? 'explicit_bucket_histogram') {
                    'explicit_bucket_histogram' => new ExplicitBucketHistogramAggregation(),
                    'base2_exponential_bucket_histogram' => new Base2ExponentialBucketHistogramAggregation(),
                }),
                logger: $context->logger,
            ),
            exportIntervalMillis: $env->int('OTEL_METRIC_EXPORT_INTERVAL') ?? 60000,
            exportTimeoutMillis: $env->int('OTEL_METRIC_EXPORT_TIMEOUT') ?? 30000,
            tracerProvider: $context->tracerProvider,
            meterProvider: $context->meterProvider,
            logger: $context->logger,
        );
    }

    public function name(): string {
        return 'otlp';
    }
}
