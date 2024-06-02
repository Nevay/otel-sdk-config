<?php declare(strict_types=1);
namespace Nevay\OTelSDK\Configuration\Env\EnvLoaders\Trace;

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
use Nevay\OTelSDK\Otlp\OtlpHttpSpanExporter;
use Nevay\OTelSDK\Otlp\ProtobufFormat;
use Nevay\OTelSDK\Trace\SpanProcessor;
use Nevay\OTelSDK\Trace\SpanProcessor\BatchSpanProcessor;
use Nevay\SPI\ServiceProviderDependency\PackageDependency;

/**
 * @implements Loader<SpanProcessor>
 */
#[PackageDependency('tbachert/otel-sdk-otlpexporter', '^0.1')]
#[PackageDependency('amphp/http-client', '^5.0')]
#[PackageDependency('amphp/socket', '^2.0')]
#[PackageDependency('league/uri', '^7.0')]
final class SpanProcessorLoaderOtlp implements Loader {

    public function load(EnvResolver $env, LoaderRegistry $registry, Context $context): SpanProcessor {
        $tlsContext = new ClientTlsContext();
        if ($clientCertificate = $env->string('OTEL_EXPORTER_OTLP_TRACES_CLIENT_CERTIFICATE') ?? $env->string('OTEL_EXPORTER_OTLP_CLIENT_CERTIFICATE')) {
            $tlsContext = $tlsContext->withCertificate(new Certificate($clientCertificate, $env->string('OTEL_EXPORTER_OTLP_TRACES_CLIENT_KEY') ?? $env->string('OTEL_EXPORTER_OTLP_CLIENT_KEY')));
        }
        if ($certificate = $env->string('OTEL_EXPORTER_OTLP_TRACES_CERTIFICATE') ?? $env->string('OTEL_EXPORTER_OTLP_CERTIFICATE')) {
            $tlsContext = $tlsContext->withCaPath($certificate);
        }

        $client = (new HttpClientBuilder())
            ->retry(0)
            ->usingPool(new UnlimitedConnectionPool(new DefaultConnectionFactory(connectContext: (new ConnectContext())->withTlsContext($tlsContext))))
            ->build();

        return new BatchSpanProcessor(
            spanExporter: new OtlpHttpSpanExporter(
                client: $client,
                endpoint: Uri\Http::new($env->string('OTEL_EXPORTER_OTLP_TRACES_ENDPOINT') ?? ($env->string('OTEL_EXPORTER_OTLP_ENDPOINT') ?? 'http://localhost:4318') . '/v1/traces'),
                format: match ($env->string('OTEL_EXPORTER_OTLP_TRACES_PROTOCOL') ?? $env->string('OTEL_EXPORTER_OTLP_PROTOCOL') ?? 'http/protobuf') {
                    'http/protobuf' => ProtobufFormat::Protobuf,
                    'http/json' => ProtobufFormat::Json,
                },
                compression: $env->string('OTEL_EXPORTER_OTLP_TRACES_COMPRESSION') ?? $env->string('OTEL_EXPORTER_OTLP_COMPRESSION'),
                headers: $env->map('OTEL_EXPORTER_OTLP_TRACES_HEADERS') ?? $env->map('OTEL_EXPORTER_OTLP_HEADERS') ?? [],
                timeout: ($env->int('OTEL_EXPORTER_OTLP_TRACES_TIMEOUT') ?? $env->int('OTEL_EXPORTER_OTLP_TIMEOUT') ?? 10000) / 1e3,
                logger: $context->logger,
            ),
            maxQueueSize: $env->int('OTEL_BSP_MAX_QUEUE_SIZE') ?? 2048,
            scheduledDelayMillis: $env->int('OTEL_BSP_SCHEDULE_DELAY') ?? 5000,
            exportTimeoutMillis: $env->int('OTEL_BSP_EXPORT_TIMEOUT') ?? 30000,
            maxExportBatchSize: $env->int('OTEL_BSP_MAX_EXPORT_BATCH_SIZE') ?? 512,
            tracerProvider: $context->tracerProvider,
            meterProvider: $context->meterProvider,
            logger: $context->logger,
        );
    }

    public function name(): string {
        return 'otlp';
    }
}
