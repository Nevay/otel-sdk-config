<?php declare(strict_types=1);
namespace Nevay\OTelSDK\Configuration\ConfigEnv\Trace;

use Nevay\OTelSDK\Trace\SpanSuppression\SemanticConventionSuppressionStrategy;
use Nevay\OTelSDK\Trace\SpanSuppressionStrategy;
use Nevay\SPI\ServiceLoader;
use OpenTelemetry\API\Configuration\ConfigEnv\EnvComponentLoader;
use OpenTelemetry\API\Configuration\ConfigEnv\EnvComponentLoaderRegistry;
use OpenTelemetry\API\Configuration\ConfigEnv\EnvResolver;
use OpenTelemetry\API\Configuration\Context;
use OpenTelemetry\API\Trace\SpanSuppression\SemanticConventionResolver;

/**
 * @implements EnvComponentLoader<SpanSuppressionStrategy>
 */
final class SpanSuppressionStrategyLoaderSemanticConvention implements EnvComponentLoader {

    public function load(EnvResolver $env, EnvComponentLoaderRegistry $registry, Context $context): SpanSuppressionStrategy {
        return new SemanticConventionSuppressionStrategy(ServiceLoader::load(SemanticConventionResolver::class));
    }

    public function name(): string {
        return 'semantic_convention';
    }
}
