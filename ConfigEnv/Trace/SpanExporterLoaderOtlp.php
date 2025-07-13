<?php declare(strict_types=1);
namespace Nevay\OTelSDK\Configuration\ConfigEnv\Trace;

use Amp\Http\Client\Connection\DefaultConnectionFactory;
use Amp\Http\Client\Connection\UnlimitedConnectionPool;
use Amp\Http\Client\HttpClientBuilder;
use Amp\Socket\Certificate;
use Amp\Socket\ClientTlsContext;
use Amp\Socket\ConnectContext;
use League\Uri;
use Nevay\OTelSDK\Configuration\Internal\Util;
use Nevay\OTelSDK\Otlp\OtlpGrpcSpanExporter;
use Nevay\OTelSDK\Otlp\OtlpHttpSpanExporter;
use Nevay\OTelSDK\Otlp\ProtobufFormat;
use Nevay\OTelSDK\Trace\SpanExporter;
use Nevay\SPI\ServiceProviderDependency\PackageDependency;
use OpenTelemetry\API\Configuration\ConfigEnv\EnvComponentLoader;
use OpenTelemetry\API\Configuration\ConfigEnv\EnvComponentLoaderRegistry;
use OpenTelemetry\API\Configuration\ConfigEnv\EnvResolver;
use OpenTelemetry\API\Configuration\Context;

/**
 * @implements EnvComponentLoader<SpanExporter>
 */
#[PackageDependency('tbachert/otel-sdk-otlpexporter', '^0.1')]
#[PackageDependency('amphp/http-client', '^5.0')]
#[PackageDependency('amphp/socket', '^2.0')]
#[PackageDependency('league/uri', '^7.0')]
final class SpanExporterLoaderOtlp implements EnvComponentLoader {

    public function load(EnvResolver $env, EnvComponentLoaderRegistry $registry, Context $context): SpanExporter {
        $tlsContext = new ClientTlsContext();
        if ($clientCertificate = $env->string('OTEL_EXPORTER_OTLP_TRACES_CLIENT_CERTIFICATE') ?? $env->string('OTEL_EXPORTER_OTLP_CLIENT_CERTIFICATE')) {
            $tlsContext = $tlsContext->withCertificate(new Certificate(
                Util::makePathAbsolute($clientCertificate),
                Util::makePathAbsolute($env->string('OTEL_EXPORTER_OTLP_TRACES_CLIENT_KEY') ?? $env->string('OTEL_EXPORTER_OTLP_CLIENT_KEY')),
            ));
        }
        if ($certificate = $env->string('OTEL_EXPORTER_OTLP_TRACES_CERTIFICATE') ?? $env->string('OTEL_EXPORTER_OTLP_CERTIFICATE')) {
            $tlsContext = $tlsContext->withCaPath(Util::makePathAbsolute($certificate));
        }

        $client = (new HttpClientBuilder())
            ->retry(0)
            ->usingPool(new UnlimitedConnectionPool(new DefaultConnectionFactory(connectContext: (new ConnectContext())->withTlsContext($tlsContext))))
            ->build();

        $format = match ($env->string('OTEL_EXPORTER_OTLP_TRACES_PROTOCOL') ?? $env->string('OTEL_EXPORTER_OTLP_PROTOCOL') ?? 'http/protobuf') {
            'http/protobuf' => ProtobufFormat::Protobuf,
            'http/json' => ProtobufFormat::Json,
            'grpc' => null,
        };
        $compression = $env->string('OTEL_EXPORTER_OTLP_TRACES_COMPRESSION') ?? $env->string('OTEL_EXPORTER_OTLP_COMPRESSION');
        $headers = $env->map('OTEL_EXPORTER_OTLP_TRACES_HEADERS') ?? $env->map('OTEL_EXPORTER_OTLP_HEADERS') ?? [];
        $timeout = ($env->int('OTEL_EXPORTER_OTLP_TRACES_TIMEOUT') ?? $env->int('OTEL_EXPORTER_OTLP_TIMEOUT') ?? 10000) / 1e3;

        return $format
            ? new OtlpHttpSpanExporter(
                client: $client,
                endpoint: Uri\Http::new($env->string('OTEL_EXPORTER_OTLP_TRACES_ENDPOINT') ?? ($env->string('OTEL_EXPORTER_OTLP_ENDPOINT') ?? 'http://localhost:4318') . '/v1/traces'),
                format: $format,
                compression: $compression,
                headers: $headers,
                timeout: $timeout,
                meterProvider: $context->meterProvider,
                logger: $context->logger,
            )
            : new OtlpGrpcSpanExporter(
                client: $client,
                endpoint: Uri\Http::new($env->string('OTEL_EXPORTER_OTLP_TRACES_ENDPOINT') ?? $env->string('OTEL_EXPORTER_OTLP_ENDPOINT') ?? 'http://localhost:4317'),
                compression: $compression,
                headers: $headers,
                timeout: $timeout,
                meterProvider: $context->meterProvider,
                logger: $context->logger,
            )
        ;
    }

    public function name(): string {
        return 'otlp';
    }
}
