<?php declare(strict_types=1);
namespace Nevay\OTelSDK\Configuration\ConfigEnv\Propagator;

use OpenTelemetry\API\Configuration\ConfigEnv\EnvComponentLoader;
use OpenTelemetry\API\Configuration\ConfigEnv\EnvComponentLoaderRegistry;
use OpenTelemetry\API\Configuration\ConfigEnv\EnvResolver;
use OpenTelemetry\API\Configuration\Context;
use OpenTelemetry\API\Trace\Propagation\TraceContextPropagator;
use OpenTelemetry\Context\Propagation\TextMapPropagatorInterface;

/**
 * @implements EnvComponentLoader<TextMapPropagatorInterface>
 */
final class TextMapPropagatorLoaderTraceContext implements EnvComponentLoader {

    public function load(EnvResolver $env, EnvComponentLoaderRegistry $registry, Context $context): TextMapPropagatorInterface {
        return TraceContextPropagator::getInstance();
    }

    public function name(): string {
        return 'tracecontext';
    }
}
