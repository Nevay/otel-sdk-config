<?php declare(strict_types=1);
namespace Nevay\OTelSDK\Configuration\ConfigEnv\Metrics;

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
use Nevay\OTelSDK\Otlp\OtlpHttpMetricExporter;
use Nevay\OTelSDK\Otlp\OtlpTemporality;
use Nevay\OTelSDK\Otlp\ProtobufFormat;
use Nevay\SPI\ServiceProviderDependency\PackageDependency;
use OpenTelemetry\API\Configuration\ConfigEnv\EnvComponentLoader;
use OpenTelemetry\API\Configuration\ConfigEnv\EnvComponentLoaderRegistry;
use OpenTelemetry\API\Configuration\ConfigEnv\EnvResolver;
use OpenTelemetry\API\Configuration\Context;
use function strtolower;

/**
 * @implements EnvComponentLoader<MetricExporter>
 */
#[PackageDependency('tbachert/otel-sdk-otlpexporter', '^0.1')]
#[PackageDependency('amphp/http-client', '^5.0')]
#[PackageDependency('amphp/socket', '^2.0')]
#[PackageDependency('league/uri', '^7.0')]
final class MetricExporterLoaderOtlp implements EnvComponentLoader {

    public function load(EnvResolver $env, EnvComponentLoaderRegistry $registry, Context $context): MetricExporter {
        $tlsContext = new ClientTlsContext();
        if ($clientCertificate = $env->string('OTEL_EXPORTER_OTLP_METRICS_CLIENT_CERTIFICATE') ?? $env->string('OTEL_EXPORTER_OTLP_CLIENT_CERTIFICATE')) {
            $tlsContext = $tlsContext->withCertificate(new Certificate(
                Util::makePathAbsolute($clientCertificate),
                Util::makePathAbsolute($env->string('OTEL_EXPORTER_OTLP_METRICS_CLIENT_KEY') ?? $env->string('OTEL_EXPORTER_OTLP_CLIENT_KEY')),
            ));
        }
        if ($certificate = $env->string('OTEL_EXPORTER_OTLP_METRICS_CERTIFICATE') ?? $env->string('OTEL_EXPORTER_OTLP_CERTIFICATE')) {
            $tlsContext = $tlsContext->withCaPath(Util::makePathAbsolute($certificate));
        }

        $client = (new HttpClientBuilder())
            ->retry(0)
            ->usingPool(new UnlimitedConnectionPool(new DefaultConnectionFactory(connectContext: (new ConnectContext())->withTlsContext($tlsContext))))
            ->build();

        $format = match ($env->string('OTEL_EXPORTER_OTLP_METRICS_PROTOCOL') ?? $env->string('OTEL_EXPORTER_OTLP_PROTOCOL') ?? 'http/protobuf') {
            'http/protobuf' => ProtobufFormat::Protobuf,
            'http/json' => ProtobufFormat::Json,
            'grpc' => null,
        };
        $compression = $env->string('OTEL_EXPORTER_OTLP_METRICS_COMPRESSION') ?? $env->string('OTEL_EXPORTER_OTLP_COMPRESSION');
        $headers = $env->map('OTEL_EXPORTER_OTLP_METRICS_HEADERS') ?? $env->map('OTEL_EXPORTER_OTLP_HEADERS') ?? [];
        $timeout = ($env->int('OTEL_EXPORTER_OTLP_METRICS_TIMEOUT') ?? $env->int('OTEL_EXPORTER_OTLP_TIMEOUT') ?? 10000) / 1e3;
        $temporalityResolver = match (strtolower($env->string('OTEL_EXPORTER_OTLP_METRICS_TEMPORALITY_PREFERENCE') ?? 'cumulative')) {
            'cumulative' => OtlpTemporality::Cumulative,
            'delta' => OtlpTemporality::Delta,
            'lowmemory' => OtlpTemporality::LowMemory,
        };
        $aggregation = (new DefaultAggregation())->with(InstrumentType::Histogram, match ($env->string('OTEL_EXPORTER_OTLP_METRICS_DEFAULT_HISTOGRAM_AGGREGATION') ?? 'explicit_bucket_histogram') {
            'explicit_bucket_histogram' => new ExplicitBucketHistogramAggregation(),
            'base2_exponential_bucket_histogram' => new Base2ExponentialBucketHistogramAggregation(),
        });

        return $format
            ? new OtlpHttpMetricExporter(
                client: $client,
                endpoint: Uri\Http::new($env->string('OTEL_EXPORTER_OTLP_METRICS_ENDPOINT') ?? ($env->string('OTEL_EXPORTER_OTLP_ENDPOINT') ?? 'http://localhost:4318') . '/v1/metrics'),
                format: $format,
                compression: $compression,
                headers: $headers,
                timeout: $timeout,
                temporalityResolver: $temporalityResolver,
                aggregation: $aggregation,
                meterProvider: $context->meterProvider,
                logger: $context->logger,
            )
            : new OtlpGrpcMetricExporter(
                client: $client,
                endpoint: Uri\Http::new($env->string('OTEL_EXPORTER_OTLP_METRICS_ENDPOINT') ?? $env->string('OTEL_EXPORTER_OTLP_ENDPOINT') ?? 'http://localhost:4317'),
                compression: $compression,
                headers: $headers,
                timeout: $timeout,
                temporalityResolver: $temporalityResolver,
                aggregation: $aggregation,
                meterProvider: $context->meterProvider,
                logger: $context->logger,
            )
        ;
    }

    public function name(): string {
        return 'otlp';
    }
}
