<?php declare(strict_types=1);
namespace Nevay\OtelSDK\Configuration\Config\Internal;

use Nevay\OtelSDK\Common\Provider\MultiProvider;
use Nevay\OtelSDK\Common\Provider\NoopProvider;
use Nevay\OtelSDK\Common\Resource;
use Nevay\OtelSDK\Configuration\Config\ComponentPlugin;
use Nevay\OtelSDK\Configuration\Config\ComponentProvider;
use Nevay\OtelSDK\Configuration\Config\ComponentProviderRegistry;
use Nevay\OtelSDK\Configuration\ConfigurationResult;
use Nevay\OtelSDK\Configuration\Context;
use Nevay\OtelSDK\Logs\LoggerProviderBuilder;
use Nevay\OtelSDK\Metrics\MeterProviderBuilder;
use Nevay\OtelSDK\Trace\TracerProviderBuilder;
use OpenTelemetry\API\Logs\NoopLoggerProvider;
use OpenTelemetry\API\Trace\NoopTracerProvider;
use OpenTelemetry\Context\Propagation\NoopTextMapPropagator;
use OpenTelemetry\Context\Propagation\TextMapPropagatorInterface;
use OpenTelemetry\SDK\Metrics\NoopMeterProvider;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;

/**
 * @internal
 */
final class ConfigurationResultOpentelemetryConfiguration implements ComponentProvider {

    /**
     * @param array{
     *     file_format: string,
     *     disabled: bool,
     *     resource: array{
     *         attributes: array,
     *         schema_url: ?string,
     *     },
     *     attribute_limits: array{
     *         attribute_value_length_limit: ?int<0, max>,
     *         attribute_count_limit: ?int<0, max>,
     *     },
     *     propagator: ?ComponentPlugin<TextMapPropagatorInterface>,
     *     tracer_provider: ?ComponentPlugin<TracerProviderBuilder>,
     *     meter_provider: ?ComponentPlugin<MeterProviderBuilder>,
     *     logger_provider: ?ComponentPlugin<LoggerProviderBuilder>,
     * } $properties
     */
    public function createPlugin(array $properties, Context $context): ConfigurationResult {
        $propagator = $properties['propagator']?->create($context) ?? NoopTextMapPropagator::getInstance();

        if ($properties['disabled']) {
            return new ConfigurationResult(
                $propagator,
                new NoopTracerProvider(),
                new NoopMeterProvider(),
                new NoopLoggerProvider(),
                new NoopProvider(),
            );
        }

        $tracerProviderBuilder = $properties['tracer_provider']?->create($context) ?? new TracerProviderBuilder();
        $meterProviderBuilder = $properties['meter_provider']?->create($context) ?? new MeterProviderBuilder();
        $loggerProviderBuilder = $properties['logger_provider']?->create($context) ?? new LoggerProviderBuilder();

        $resource = Resource::create(
            $properties['resource']['attributes'],
            $properties['resource']['schema_url'],
        );
        $tracerProviderBuilder->addResource($resource);
        $meterProviderBuilder->addResource($resource);
        $loggerProviderBuilder->addResource($resource);

        $attributeCountLimit = $properties['attribute_limits']['attribute_count_limit'];
        $attributeValueLengthLimit = $properties['attribute_limits']['attribute_value_length_limit'];
        $tracerProviderBuilder->setAttributeLimits($attributeCountLimit, $attributeValueLengthLimit);
        $loggerProviderBuilder->setAttributeLimits($attributeCountLimit, $attributeValueLengthLimit);

        $context->processor?->process($tracerProviderBuilder, $meterProviderBuilder, $loggerProviderBuilder);

        $resource = Resource::default();
        $tracerProviderBuilder->addResource($resource);
        $meterProviderBuilder->addResource($resource);
        $loggerProviderBuilder->addResource($resource);

        $tracerProvider = $tracerProviderBuilder->build($context->logger);
        $meterProvider = $meterProviderBuilder->build($context->logger);
        $loggerProvider = $loggerProviderBuilder->build($context->logger);

        return new ConfigurationResult(
            $propagator,
            $tracerProvider,
            $meterProvider,
            $loggerProvider,
            new MultiProvider([
                $tracerProvider,
                $meterProvider,
                $loggerProvider,
            ]),
        );
    }

    public function getConfig(ComponentProviderRegistry $registry): ArrayNodeDefinition {
        $node = new ArrayNodeDefinition('opentelemetry_configuration');
        $node
            ->addDefaultsIfNotSet()
            ->ignoreExtraKeys()
            ->children()
                ->scalarNode('file_format')
                    ->isRequired()
                    ->example('0.1')
                    ->validate()->ifNotInArray(['0.1'])->thenInvalid('Unsupported version')->end()
                ->end()
                ->booleanNode('disabled')->defaultFalse()->end()
                ->arrayNode('resource')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->arrayNode('attributes')
                            ->variablePrototype()->end()
                        ->end()
                        ->scalarNode('schema_url')->defaultNull()->end()
                    ->end()
                ->end()
                ->arrayNode('attribute_limits')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->integerNode('attribute_value_length_limit')->min(0)->defaultValue(4096)->end()
                        ->integerNode('attribute_count_limit')->min(0)->defaultValue(128)->end()
                    ->end()
                ->end()
                ->append(ComponentPlugin::provider('propagator', TextMapPropagatorInterface::class, $registry))
                ->append(ComponentPlugin::toPlugin(new TracerProviderBuilderTracerProvider(), $registry))
                ->append(ComponentPlugin::toPlugin(new MeterProviderBuilderMeterProvider(), $registry))
                ->append(ComponentPlugin::toPlugin(new LoggerProviderBuilderLoggerProvider(), $registry))
            ->end()
        ;

        return $node;
    }
}
