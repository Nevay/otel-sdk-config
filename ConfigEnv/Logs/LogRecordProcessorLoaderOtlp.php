<?php declare(strict_types=1);
namespace Nevay\OTelSDK\Configuration\ConfigEnv\Logs;

use Amp\Http\Client\Connection\DefaultConnectionFactory;
use Amp\Http\Client\Connection\UnlimitedConnectionPool;
use Amp\Http\Client\HttpClientBuilder;
use Amp\Socket\Certificate;
use Amp\Socket\ClientTlsContext;
use Amp\Socket\ConnectContext;
use League\Uri;
use Nevay\OTelSDK\Configuration\Internal\Util;
use Nevay\OTelSDK\Logs\LogRecordProcessor;
use Nevay\OTelSDK\Logs\LogRecordProcessor\BatchLogRecordProcessor;
use Nevay\OTelSDK\Otlp\OtlpGrpcLogRecordExporter;
use Nevay\OTelSDK\Otlp\OtlpHttpLogRecordExporter;
use Nevay\OTelSDK\Otlp\ProtobufFormat;
use Nevay\SPI\ServiceProviderDependency\PackageDependency;
use OpenTelemetry\API\Configuration\ConfigEnv\EnvComponentLoader;
use OpenTelemetry\API\Configuration\ConfigEnv\EnvComponentLoaderRegistry;
use OpenTelemetry\API\Configuration\ConfigEnv\EnvResolver;
use OpenTelemetry\API\Configuration\Context;

/**
 * @implements EnvComponentLoader<LogRecordProcessor>
 */
#[PackageDependency('tbachert/otel-sdk-otlpexporter', '^0.1')]
#[PackageDependency('amphp/http-client', '^5.0')]
#[PackageDependency('amphp/socket', '^2.0')]
#[PackageDependency('league/uri', '^7.0')]
final class LogRecordProcessorLoaderOtlp implements EnvComponentLoader {

    public function load(EnvResolver $env, EnvComponentLoaderRegistry $registry, Context $context): LogRecordProcessor {
        $tlsContext = new ClientTlsContext();
        if ($clientCertificate = $env->string('OTEL_EXPORTER_OTLP_LOGS_CLIENT_CERTIFICATE') ?? $env->string('OTEL_EXPORTER_OTLP_CLIENT_CERTIFICATE')) {
            $tlsContext = $tlsContext->withCertificate(new Certificate(
                Util::makePathAbsolute($clientCertificate),
                Util::makePathAbsolute($env->string('OTEL_EXPORTER_OTLP_LOGS_CLIENT_KEY') ?? $env->string('OTEL_EXPORTER_OTLP_CLIENT_KEY')),
            ));
        }
        if ($certificate = $env->string('OTEL_EXPORTER_OTLP_LOGS_CERTIFICATE') ?? $env->string('OTEL_EXPORTER_OTLP_CERTIFICATE')) {
            $tlsContext = $tlsContext->withCaPath(Util::makePathAbsolute($certificate));
        }

        $client = (new HttpClientBuilder())
            ->retry(0)
            ->usingPool(new UnlimitedConnectionPool(new DefaultConnectionFactory(connectContext: (new ConnectContext())->withTlsContext($tlsContext))))
            ->build();

        $format = match ($env->string('OTEL_EXPORTER_OTLP_LOGS_PROTOCOL') ?? $env->string('OTEL_EXPORTER_OTLP_PROTOCOL') ?? 'http/protobuf') {
            'http/protobuf' => ProtobufFormat::Protobuf,
            'http/json' => ProtobufFormat::Json,
            'grpc' => null,
        };
        $compression = $env->string('OTEL_EXPORTER_OTLP_LOGS_COMPRESSION') ?? $env->string('OTEL_EXPORTER_OTLP_COMPRESSION');
        $headers = $env->map('OTEL_EXPORTER_OTLP_LOGS_HEADERS') ?? $env->map('OTEL_EXPORTER_OTLP_HEADERS') ?? [];
        $timeout = ($env->int('OTEL_EXPORTER_OTLP_LOGS_TIMEOUT') ?? $env->int('OTEL_EXPORTER_OTLP_TIMEOUT') ?? 10000) / 1e3;

        return new BatchLogRecordProcessor(
            logRecordExporter: $format
                ? new OtlpHttpLogRecordExporter(
                    client: $client,
                    endpoint: Uri\Http::new($env->string('OTEL_EXPORTER_OTLP_LOGS_ENDPOINT') ?? ($env->string('OTEL_EXPORTER_OTLP_ENDPOINT') ?? 'http://localhost:4318') . '/v1/logs'),
                    format: $format,
                    compression: $compression,
                    headers: $headers,
                    timeout: $timeout,
                    meterProvider: $context->meterProvider,
                    logger: $context->logger,
                )
                : new OtlpGrpcLogRecordExporter(
                    client: $client,
                    endpoint: Uri\Http::new($env->string('OTEL_EXPORTER_OTLP_LOGS_ENDPOINT') ?? $env->string('OTEL_EXPORTER_OTLP_ENDPOINT') ?? 'http://localhost:4317'),
                    compression: $compression,
                    headers: $headers,
                    timeout: $timeout,
                    meterProvider: $context->meterProvider,
                    logger: $context->logger,
                ),
            maxQueueSize: $env->int('OTEL_BLRP_MAX_QUEUE_SIZE') ?? 2048,
            scheduledDelayMillis: $env->int('OTEL_BLRP_SCHEDULE_DELAY') ?? 5000,
            exportTimeoutMillis: $env->int('OTEL_BLRP_EXPORT_TIMEOUT') ?? 30000,
            maxExportBatchSize: $env->int('OTEL_BLRP_MAX_EXPORT_BATCH_SIZE') ?? 512,
            tracerProvider: $context->tracerProvider,
            meterProvider: $context->meterProvider,
            logger: $context->logger,
        );
    }

    public function name(): string {
        return 'otlp';
    }
}
