<?php declare(strict_types=1);
namespace Nevay\OtelSDK\Configuration\Config;

use Exception;
use Nevay\OtelSDK\Configuration\Config\Internal\ArrayNodePluginDefinition;
use Nevay\OtelSDK\Configuration\Config\Internal\MutableComponentProviderRegistry;
use Nevay\OtelSDK\Configuration\Config\Internal\RecursionProtectedComponentProviderRegistry;
use Nevay\OtelSDK\Configuration\Context;
use Nevay\OtelSDK\Configuration\Exception\UnhandledPluginException;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\Definition\Builder\NodeDefinition;
use Symfony\Component\Config\Definition\Builder\VariableNodeDefinition;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;
use function array_key_first;
use function array_keys;
use function array_map;
use function count;
use function implode;
use function sprintf;

/**
 * A component plugin that can be used to create a component.
 *
 * @template T
 */
final class ComponentPlugin {

    /**
     * @param array $properties resolved properties according to component provider config
     * @param ComponentProvider $provider component provider used to create the component
     *
     * @internal
     */
    public function __construct(
        private readonly array $properties,
        private readonly ComponentProvider $provider,
    ) {}

    /**
     * Creates the component that is provided by this plugin.
     *
     * @param Context $context context used for creation
     * @return T created component
     * @throws UnhandledPluginException if any exception is thrown by the plugin provider
     */
    public function create(Context $context): mixed {
        try {
            return $this->provider->createPlugin($this->properties, $context);
        } catch (UnhandledPluginException $e) {
            throw $e;
        } catch (Exception $e) {
            throw new UnhandledPluginException($e->getMessage(), $e->getCode(), $e);
        }
    }

    /**
     * Creates a node to specify a component plugin.
     *
     * `$name: ?ComponentPlugin<$type>`
     *
     * ```
     * $name:
     *   provider1:
     *     property: value
     *     anotherProperty: value
     * ```
     *
     * @param string $name name of configuration node
     * @param string $type type of the component plugin
     * @param ComponentProviderRegistry $registry registry containing all available component providers
     */
    public static function provider(string $name, string $type, ComponentProviderRegistry $registry): NodeDefinition {
        if (!$registry->getProviders($type)) {
            return (new VariableNodeDefinition($name))
                ->defaultNull()
                ->validate()
                    ->always()
                    ->thenInvalid(sprintf('Component "%s" cannot be configured, it does not have any associated provider', $type))
                ->end();
        }

        $node = new ArrayNodePluginDefinition($name);
        self::applyToArrayNode($node, $type, $registry);

        return $node;
    }

    /**
     * Creates a node to specify a list of component plugin.
     *
     * `$name: list<ComponentPlugin<$type>>`
     *
     * ```
     * $name:
     * - provider1:
     *      property: value
     *      anotherProperty: value
     * - provider2:
     *      property: value
     *      anotherProperty: value
     * ```
     *
     * @param string $name name of configuration node
     * @param string $type type of the component plugin
     * @param ComponentProviderRegistry $registry registry containing all available component providers
     */
    public static function providerList(string $name, string $type, ComponentProviderRegistry $registry): ArrayNodeDefinition {
        $node = new ArrayNodeDefinition($name);
        self::applyToArrayNode($node->arrayPrototype(), $type, $registry);

        return $node;
    }

    /**
     * Creates a node to specify a list of component plugin names.
     *
     * The providers cannot have required properties.
     *
     * `$name: list<ComponentPlugin<$type>>`
     *
     * ```
     * $name: [provider1, provider2]
     * ```
     *
     * @param string $name name of configuration node
     * @param string $type type of the component plugin
     * @param ComponentProviderRegistry $registry registry containing all available component providers
     */
    public static function providerNames(string $name, string $type, ComponentProviderRegistry $registry): ArrayNodeDefinition {
        $node = new ArrayNodeDefinition($name);

        $providers = $registry->getProviders($type);
        foreach ($providers as $providerName => $provider) {
            try {
                $provider->getConfig(new MutableComponentProviderRegistry())->getNode(true)->finalize([]);
            } catch (InvalidConfigurationException) {
                unset($providers[$providerName]);
            }
        }
        if ($providers) {
            $node->enumPrototype()->values(array_keys($providers))->end();
            $node->validate()->always(static function(array $value) use ($providers): array {
                $plugins = [];
                foreach ($value as $name) {
                    $plugins[] = new ComponentPlugin([], $providers[$name]);
                }

                return $plugins;
            });
        }

        return $node;
    }

    /**
     * Creates a node to specify a list of component plugin names.
     *
     * The providers cannot have required properties.
     *
     * `$name: ?ComponentPlugin<$type>
     *
     * ```
     * $name:
     *     property: value
     *     anotherProperty: value
     * ```
     *
     * @param ComponentProvider $provider component provider to use, does not have to be registered in `$registry`
     * @param ComponentProviderRegistry $registry registry containing all available component providers
     *
     * @internal
     */
    public static function toPlugin(ComponentProvider $provider, ComponentProviderRegistry $registry): NodeDefinition {
        $node = ArrayNodePluginDefinition::fromNodeDefinition($provider->getConfig($registry));
        $node->validate()->always(static fn(array $value): ComponentPlugin => new ComponentPlugin($value, $provider));

        return $node;
    }

    private static function applyToArrayNode(ArrayNodeDefinition $node, string $type, ComponentProviderRegistry $registry): void {
        $node->info(sprintf('Component "%s"', $type));
        $node->performNoDeepMerging();

        $providers = $registry->getProviders($type);
        foreach ($providers as $name => $provider) {
            $node->children()->append($provider->getConfig(new RecursionProtectedComponentProviderRegistry($registry, $type, $name)));
        }
        $node->validate()->always(static function(array $value) use ($providers, $type): ComponentPlugin {
            if (count($value) !== 1) {
                throw new InvalidConfigurationException(sprintf('Component "%s" must have exactly one element defined, got %s',
                    $type, implode(', ', array_map(json_encode(...), array_keys($value))) ?: 'none'));
            }

            $name = array_key_first($value);

            return new ComponentPlugin($value[$name], $providers[$name]);
        });
    }
}
