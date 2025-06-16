<?php declare(strict_types=1);
namespace Nevay\OTelSDK\Configuration\ConfigEnv\Trace;

use Nevay\OTelSDK\Trace\SpanProcessor;
use Nevay\OTelSDK\Trace\SpanProcessor\NoopSpanProcessor;
use OpenTelemetry\API\Configuration\ConfigEnv\EnvComponentLoader;
use OpenTelemetry\API\Configuration\ConfigEnv\EnvComponentLoaderRegistry;
use OpenTelemetry\API\Configuration\ConfigEnv\EnvResolver;
use OpenTelemetry\API\Configuration\Context;

/**
 * @implements EnvComponentLoader<SpanProcessor>
 */
final class SpanProcessorLoaderNone implements EnvComponentLoader {

    public function load(EnvResolver $env, EnvComponentLoaderRegistry $registry, Context $context): SpanProcessor {
        return new NoopSpanProcessor();
    }

    public function name(): string {
        return 'none';
    }
}
