<?php declare(strict_types=1);
namespace Nevay\OtelSDK\Configuration\Config\Internal;

use Nevay\OtelSDK\Configuration\Config\ComponentPlugin;
use Nevay\OtelSDK\Configuration\Config\ComponentProvider;
use Nevay\OtelSDK\Configuration\Config\ComponentProviderRegistry;
use Nevay\OtelSDK\Configuration\Context;
use Nevay\OtelSDK\Logs\LoggerProviderBuilder;
use Nevay\OtelSDK\Logs\LogRecordProcessor;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;

/**
 * @internal
 */
final class LoggerProviderBuilderLoggerProvider implements ComponentProvider {

    /**
     * @param array{
     *     limits: array{
     *         attribute_value_length_limit: ?int<0, max>,
     *         attribute_count_limit: ?int<0, max>,
     *     },
     *     processors: list<ComponentPlugin<LogRecordProcessor>>,
     * } $properties
     */
    public function createPlugin(array $properties, Context $context): LoggerProviderBuilder {
        $loggerProviderBuilder = new LoggerProviderBuilder();

        $loggerProviderBuilder->setLogRecordAttributeLimits(
            $properties['limits']['attribute_count_limit'],
            $properties['limits']['attribute_count_limit'],
        );
        foreach ($properties['processors'] as $processor) {
            $loggerProviderBuilder->addLogRecordProcessor($processor->create($context));
        }

        return $loggerProviderBuilder;
    }

    public function getConfig(ComponentProviderRegistry $registry): ArrayNodeDefinition {
        $node = new ArrayNodeDefinition('logger_provider');
        $node
            ->children()
                ->arrayNode('limits')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->integerNode('attribute_value_length_limit')->min(0)->defaultNull()->end()
                        ->integerNode('attribute_count_limit')->min(0)->defaultNull()->end()
                    ->end()
                ->end()
                ->append(ComponentPlugin::providerList('processors', LogRecordProcessor::class, $registry))
            ->end()
        ;

        return $node;
    }
}
