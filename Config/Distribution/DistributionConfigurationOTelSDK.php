<?php declare(strict_types=1);
namespace Nevay\OTelSDK\Configuration\Config\Distribution;

use Nevay\OTelSDK\Configuration\Distribution\DistributionConfiguration;
use Nevay\OTelSDK\Configuration\Distribution\OTelSDKConfiguration;
use Nevay\OTelSDK\Configuration\Internal\File\FileWatcher;
use Nevay\OTelSDK\Trace\SpanSuppression\NoopSuppressionStrategy;
use Nevay\OTelSDK\Trace\SpanSuppressionStrategy;
use OpenTelemetry\API\Configuration\Config\ComponentPlugin;
use OpenTelemetry\API\Configuration\Config\ComponentProvider;
use OpenTelemetry\API\Configuration\Config\ComponentProviderRegistry;
use OpenTelemetry\API\Configuration\Context;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\Definition\Builder\NodeBuilder;

/**
 * @implements ComponentProvider<DistributionConfiguration>
 */
final class DistributionConfigurationOTelSDK implements ComponentProvider {

    /**
     * @param array{
     *     shutdown_timeout: ?float,
     *     "span_suppression_strategy/development": ?ComponentPlugin<SpanSuppressionStrategy>,
     *     "watcher/development": ?ComponentPlugin<FileWatcher>
     * } $properties
     */
    public function createPlugin(array $properties, Context $context): DistributionConfiguration {
        return new OTelSDKConfiguration(
            shutdownTimeout: $properties['shutdown_timeout'],
            spanSuppressionStrategy: $properties['span_suppression_strategy/development']?->create($context) ?? new NoopSuppressionStrategy(),
            watcher: $properties['watcher/development']?->create($context),
        );
    }

    public function getConfig(ComponentProviderRegistry $registry, NodeBuilder $builder): ArrayNodeDefinition {
        $node = $builder->arrayNode('tbachert/otel-sdk');
        $node
            ->children()
                ->floatNode('shutdown_timeout')->min(0)->defaultNull()->end()
                ->append($registry->component('span_suppression_strategy/development', SpanSuppressionStrategy::class))
                ->append($registry->component('watcher/development', FileWatcher::class))
            ->end()
        ;

        return $node;
    }
}
