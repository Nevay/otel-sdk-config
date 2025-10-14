<?php declare(strict_types=1);
namespace Nevay\OTelSDK\Configuration\ConfigEnv\Trace;

use Nevay\OTelSDK\Jaeger\ComposableJaegerRemoteSampler;
use Nevay\OTelSDK\Jaeger\GrpcSamplingManager;
use Nevay\OTelSDK\Trace\Sampler;
use Nevay\OTelSDK\Trace\Sampler\Composable\ComposableTraceIdRatioBasedSampler;
use Nevay\OTelSDK\Trace\Sampler\CompositeSampler;
use Nevay\SPI\ServiceProviderDependency\PackageDependency;
use OpenTelemetry\API\Configuration\ConfigEnv\EnvComponentLoader;
use OpenTelemetry\API\Configuration\ConfigEnv\EnvComponentLoaderRegistry;
use OpenTelemetry\API\Configuration\ConfigEnv\EnvResolver;
use OpenTelemetry\API\Configuration\Context;

/**
 * @implements EnvComponentLoader<Sampler>
 */
#[PackageDependency('tbachert/otel-sdk-jaegerremotesampler', '^0.1')]
final class SamplerLoaderJaegerRemote implements EnvComponentLoader {

    public function load(EnvResolver $env, EnvComponentLoaderRegistry $registry, Context $context): Sampler {
        $params = $env->map('OTEL_TRACES_SAMPLER_ARG') ?? [];

        $endpoint = $params['endpoint'] ?? 'http://localhost:5779';
        $pollingIntervalMs = (int) ($params['pollingIntervalMs'] ?? 60000);
        $initialSamplingRate = (float) ($params['initialSamplingRate'] ?? 0.001);

        return new CompositeSampler(
            sampler: new ComposableJaegerRemoteSampler(
                serviceName: '',
                initialSampler: new ComposableTraceIdRatioBasedSampler($initialSamplingRate),
                samplingManager: new GrpcSamplingManager($endpoint),
                pollingIntervalMillis: $pollingIntervalMs,
                logger: $context->logger,
            ),
            logger: $context->logger,
        );
    }

    public function name(): string {
        return 'jaeger_remote';
    }
}
