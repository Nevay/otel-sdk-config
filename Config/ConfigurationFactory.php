<?php declare(strict_types=1);
namespace Nevay\OtelSDK\Configuration\Config;

use Nevay\OtelSDK\Configuration\Config\Internal\ConfigurationResultOpentelemetryConfiguration;
use Nevay\OtelSDK\Configuration\Config\Internal\MutableComponentProviderRegistry;
use Nevay\OtelSDK\Configuration\ConfigurationResult;
use Nevay\OtelSDK\Configuration\Context;
use Nevay\OtelSDK\Configuration\Exception\InvalidConfigurationException;
use Nevay\OtelSDK\Configuration\Exception\UnhandledPluginException;
use Nevay\SPI\ServiceLoader;
use Symfony\Component\Config\Definition\Dumper\YamlReferenceDumper;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException as SymfonyInvalidConfigurationException;
use Symfony\Component\Config\Definition\NodeInterface;

final class ConfigurationFactory {

    private function __construct(
        private readonly NodeInterface $node,
    ) {}

    /**
     * Creates a configuration factory that supports all registered component
     * providers.
     *
     * @return ConfigurationFactory configuration factory
     *
     * @see ComponentProvider
     * @see ServiceLoader::register()
     */
    public static function create(): ConfigurationFactory {
        $registry = self::createDefaultRegistry();

        foreach (ServiceLoader::load(ComponentProvider::class) as $provider) {
            $registry->register($provider);
        }

        $root = ComponentPlugin::toPlugin(new ConfigurationResultOpentelemetryConfiguration(), $registry)
            ->getNode(forceRootNode: true);

        return new self($root);
    }

    /**
     * @param Context $context context to use for component creation
     * @param array $config config to process
     * @param array ...$configs additional configs to process
     * @return ConfigurationResult resolved configuration
     * @throws InvalidConfigurationException if the provided configuration is invalid
     * @throws UnhandledPluginException if a plugin throws an exception
     */
    public function load(Context $context, array $config, array ...$configs): ConfigurationResult {
        return $this->process([$config, ...$configs])->create($context);
    }

    /**
     * @return ComponentPlugin<ConfigurationResult>
     */
    private function process(array $configs): ComponentPlugin {
        try {
            $properties = [];
            foreach ($configs as $config) {
                $properties = $this->node->merge($properties, $this->node->normalize($config));
            }

            return $this->node->finalize($properties);
        } catch (SymfonyInvalidConfigurationException $e) {
            throw new InvalidConfigurationException($e->getMessage(), $e->getCode(), $e);
        }
    }

    public function __toString(): string {
        return (new YamlReferenceDumper())->dumpNode($this->node);
    }

    private static function createDefaultRegistry(): MutableComponentProviderRegistry {
        $registry = new MutableComponentProviderRegistry();

        // propagators
        $registry->register(new Propagator\TextMapPropagatorB3());
        $registry->register(new Propagator\TextMapPropagatorB3Multi());
        $registry->register(new Propagator\TextMapPropagatorBaggage());
        $registry->register(new Propagator\TextMapPropagatorComposite());
        $registry->register(new Propagator\TextMapPropagatorJaeger());
        $registry->register(new Propagator\TextMapPropagatorTraceContext());

        // trace
        $registry->register(new Trace\SamplerAlwaysOff());
        $registry->register(new Trace\SamplerAlwaysOn());
        $registry->register(new Trace\SamplerParentBased());
        $registry->register(new Trace\SamplerTraceIdRatioBased());
        $registry->register(new Trace\SpanExporterConsole());
        $registry->register(new Trace\SpanExporterOtlp());
        $registry->register(new Trace\SpanProcessorBatch());
        $registry->register(new Trace\SpanProcessorSimple());

        // metrics
        $registry->register(new Metrics\AggregationResolverDefault());
        $registry->register(new Metrics\AggregationResolverDrop());
        $registry->register(new Metrics\AggregationResolverExplicitBucketHistogram());
        $registry->register(new Metrics\AggregationResolverLastValue());
        $registry->register(new Metrics\AggregationResolverSum());
        $registry->register(new Metrics\MetricExporterConsole());
        $registry->register(new Metrics\MetricExporterOtlp());
        $registry->register(new Metrics\MetricExporterPrometheus());
        $registry->register(new Metrics\MetricReaderPeriodic());
        $registry->register(new Metrics\MetricReaderPull());

        // logs
        $registry->register(new Logs\LogRecordExporterConsole());
        $registry->register(new Logs\LogRecordExporterOtlp());
        $registry->register(new Logs\LogRecordProcessorBatch());
        $registry->register(new Logs\LogRecordProcessorSimple());

        return $registry;
    }
}
