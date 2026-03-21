<?php declare(strict_types=1);
namespace Nevay\OTelSDK\Configuration\ConfigEnv\Trace;

use Nevay\OTelSDK\Trace\SpanSuppression\SpanKindSuppressionStrategy;
use Nevay\OTelSDK\Trace\SpanSuppressionStrategy;
use OpenTelemetry\API\Configuration\ConfigEnv\EnvComponentLoader;
use OpenTelemetry\API\Configuration\ConfigEnv\EnvComponentLoaderRegistry;
use OpenTelemetry\API\Configuration\ConfigEnv\EnvResolver;
use OpenTelemetry\API\Configuration\Context;

/**
 * @implements EnvComponentLoader<SpanSuppressionStrategy>
 */
final class SpanSuppressionStrategyLoaderSpanKind implements EnvComponentLoader {

    public function load(EnvResolver $env, EnvComponentLoaderRegistry $registry, Context $context): SpanSuppressionStrategy {
        return new SpanKindSuppressionStrategy();
    }

    public function name(): string {
        return 'span_kind';
    }
}
