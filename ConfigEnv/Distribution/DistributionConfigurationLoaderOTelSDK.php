<?php declare(strict_types=1);
namespace Nevay\OTelSDK\Configuration\ConfigEnv\Distribution;

use Nevay\OTelSDK\Configuration\Distribution\DistributionConfiguration;
use Nevay\OTelSDK\Configuration\Distribution\OTelSDKConfiguration;
use Nevay\OTelSDK\Trace\SpanSuppression\NoopSuppressionStrategy;
use Nevay\OTelSDK\Trace\SpanSuppressionStrategy;
use OpenTelemetry\API\Configuration\ConfigEnv\EnvComponentLoader;
use OpenTelemetry\API\Configuration\ConfigEnv\EnvComponentLoaderRegistry;
use OpenTelemetry\API\Configuration\ConfigEnv\EnvResolver;
use OpenTelemetry\API\Configuration\Context;

/**
 * @implements EnvComponentLoader<DistributionConfiguration>
 */
final class DistributionConfigurationLoaderOTelSDK implements EnvComponentLoader {

    public function load(EnvResolver $env, EnvComponentLoaderRegistry $registry, Context $context): DistributionConfiguration {
        $spanSuppressionStrategyName = $env->string('OTEL_PHP_EXPERIMENTAL_SPAN_SUPPRESSION_STRATEGY') ?? 'none';

        return new OTelSDKConfiguration(
            shutdownTimeout: $env->numeric('OTEL_PHP_SHUTDOWN_TIMEOUT'),
            spanSuppressionStrategy: match ($spanSuppressionStrategyName) {
                'none' => new NoopSuppressionStrategy(),
                default => $registry->load(SpanSuppressionStrategy::class, $spanSuppressionStrategyName, $env, $context),
            },
        );
    }

    public function name(): string {
        return self::class;
    }
}
