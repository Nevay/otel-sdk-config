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
        $registry = new MutableComponentProviderRegistry();
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
}
