<?php declare(strict_types=1);
namespace Nevay\OTelSDK\Configuration\Config;

use Nevay\OTelSDK\Configuration\Cache\EnvResource;
use Nevay\OTelSDK\Configuration\Cache\ResourceCollection;
use Nevay\OTelSDK\Configuration\Config\Internal\ConfigurationResultOpentelemetryConfiguration;
use Nevay\OTelSDK\Configuration\Config\Internal\MutableComponentProviderRegistry;
use Nevay\OTelSDK\Configuration\Config\Internal\TracingEnvReader;
use Nevay\OTelSDK\Configuration\Env\EnvReader;
use Nevay\OTelSDK\Configuration\Exception\InvalidConfigurationException;
use Nevay\SPI\ServiceLoader;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\Definition\Builder\BooleanNodeDefinition;
use Symfony\Component\Config\Definition\Builder\FloatNodeDefinition;
use Symfony\Component\Config\Definition\Builder\IntegerNodeDefinition;
use Symfony\Component\Config\Definition\Builder\NodeDefinition;
use Symfony\Component\Config\Definition\Builder\ScalarNodeDefinition;
use Symfony\Component\Config\Definition\Dumper\YamlReferenceDumper;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException as SymfonyInvalidConfigurationException;
use Symfony\Component\Config\Definition\NodeInterface;
use function filter_var;
use function preg_replace_callback;
use const FILTER_DEFAULT;
use const FILTER_NULL_ON_FAILURE;
use const FILTER_VALIDATE_BOOLEAN;
use const FILTER_VALIDATE_FLOAT;
use const FILTER_VALIDATE_INT;

final class ConfigurationFactory {

    private function __construct(
        private readonly NodeInterface $node,
        private readonly TracingEnvReader $envReader,
    ) {}

    /**
     * Creates a configuration factory that supports all registered component providers.
     *
     * @param EnvReader $envReader env reader to use for environment variable substitution
     * @return ConfigurationFactory configuration factory
     *
     * @see ComponentProvider
     * @see ServiceLoader::register()
     */
    public static function create(EnvReader $envReader): ConfigurationFactory {
        $registry = new MutableComponentProviderRegistry();
        foreach (ServiceLoader::load(ComponentProvider::class) as $provider) {
            $registry->register($provider);
        }

        $node = ComponentPlugin::toPlugin(new ConfigurationResultOpentelemetryConfiguration(), $registry);

        $envReader = new TracingEnvReader($envReader);
        self::applyEnvSubstitution($node, $envReader);

        return new self($node->getNode(forceRootNode: true), $envReader);
    }

    /**
     * @param iterable<array> $configs configs to process
     * @param ResourceCollection|null $resources resource collection used for cache invalidation
     * @return Configuration resolved configuration
     * @throws InvalidConfigurationException if the provided configuration is invalid
     */
    public function process(iterable $configs, ?ResourceCollection $resources = null): Configuration {
        try {
            $properties = [];
            foreach ($configs as $config) {
                $properties = $this->node->merge($properties, $this->node->normalize($config));
            }

            return new Configuration($this->node->finalize($properties));
        } catch (SymfonyInvalidConfigurationException $e) {
            throw new InvalidConfigurationException($e->getMessage(), $e->getCode(), $e);
        } finally {
            foreach ($this->envReader->collect() as $name => $value) {
                $resources?->add(new EnvResource($name, $value));
            }
        }
    }

    private static function applyEnvSubstitution(NodeDefinition $node, EnvReader $envReader): void {
        if ($node instanceof ScalarNodeDefinition) {
            $filter = match (true) {
                $node instanceof BooleanNodeDefinition => FILTER_VALIDATE_BOOLEAN,
                $node instanceof IntegerNodeDefinition => FILTER_VALIDATE_INT,
                $node instanceof FloatNodeDefinition => FILTER_VALIDATE_FLOAT,
                default => FILTER_DEFAULT,
            };
            $node->beforeNormalization()->ifString()->then(static function (string $value) use ($filter, $envReader): mixed {
                $replaced = preg_replace_callback(
                    '/\$\{(?<ENV_NAME>[a-zA-Z_][a-zA-Z0-9_]*)}/',
                    static fn(array $matches): string => $envReader->read($matches['ENV_NAME']) ?? '',
                    $value,
                    -1,
                    $count,
                );

                if (!$count) {
                    return $value;
                }

                return filter_var($replaced, $filter, FILTER_NULL_ON_FAILURE) ?? $replaced;
            });
        }

        if ($node instanceof ArrayNodeDefinition) {
            foreach ($node->getChildNodeDefinitions() as $nodeDefinition) {
                self::applyEnvSubstitution($nodeDefinition, $envReader);
            }
        }
    }

    public function __toString(): string {
        return (new YamlReferenceDumper())->dumpNode($this->node);
    }
}
