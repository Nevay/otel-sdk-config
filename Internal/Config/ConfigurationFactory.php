<?php declare(strict_types=1);
namespace Nevay\OTelSDK\Configuration\Internal\Config;

use Exception;
use Nevay\OTelSDK\Configuration\Env\EnvReader;
use Nevay\OTelSDK\Configuration\Internal\Config\NodeDefinition\ArrayNodeDefinition;
use Nevay\OTelSDK\Configuration\Internal\Config\NodeDefinition\BooleanNodeDefinition;
use Nevay\OTelSDK\Configuration\Internal\Config\NodeDefinition\EnumNodeDefinition;
use Nevay\OTelSDK\Configuration\Internal\Config\NodeDefinition\FloatNodeDefinition;
use Nevay\OTelSDK\Configuration\Internal\Config\NodeDefinition\IntegerNodeDefinition;
use Nevay\OTelSDK\Configuration\Internal\Config\NodeDefinition\ScalarNodeDefinition;
use Nevay\OTelSDK\Configuration\Internal\Config\NodeDefinition\StringNodeDefinition;
use Nevay\OTelSDK\Configuration\Internal\Config\NodeDefinition\VariableNodeDefinition;
use OpenTelemetry\API\Configuration\Config\ComponentPlugin;
use OpenTelemetry\API\Configuration\Config\ComponentProvider;
use Symfony\Component\Config\Definition\Builder\NodeBuilder;
use Symfony\Component\Config\Definition\Builder\NodeDefinition;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\Config\Loader\DelegatingLoader;
use Symfony\Component\Config\Loader\LoaderResolver;
use Symfony\Component\Config\Resource\SelfCheckingResourceChecker;
use Symfony\Component\Config\ResourceCheckerConfigCache;
use Symfony\Component\VarExporter\VarExporter;
use Throwable;
use function class_exists;
use function getcwd;
use function is_file;
use function serialize;
use function sprintf;
use function var_export;

/**
 * @template T
 *
 * @internal
 */
final class ConfigurationFactory {

    private readonly CompiledConfigurationFactory $compiledFactory;

    /**
     * @param iterable<ComponentProvider> $componentProviders
     * @param ComponentProvider<T> $rootComponent
     * @param EnvReader $envReader
     */
    public function __construct(
        private readonly iterable $componentProviders,
        private readonly ComponentProvider $rootComponent,
        private readonly EnvReader $envReader,
    ) {}

    /**
     * @param array $configs configs to process
     * @param ResourceCollection|null $resources resources that can be used for cache invalidation
     * @return ComponentPlugin<T> processed component plugin
     * @throws InvalidConfigurationException if the configuration is invalid
     */
    public function process(array $configs, ?ResourceCollection $resources = null): ComponentPlugin {
        return ($this->compiledFactory ??= $this->compileFactory())
            ->process($configs, $resources);
    }

    /**
     * @param string|list<string> $file path(s) to parse
     * @param string|null $cacheFile path to cache parsed configuration to
     * @param bool $debug will check for cache freshness if debug mode enabled
     * @return ComponentPlugin parsed component plugin
     * @throws Exception if loading of a configuration file fails for any reason
     * @throws InvalidConfigurationException if the configuration is invalid
     * @throws Throwable if a cache file is given and a non-serializable component provider is used
     *
     * @see https://opentelemetry.io/docs/specs/otel/configuration/sdk/#parse
     */
    public function parseFile(
        string|array $file,
        ?string $cacheFile = null,
        bool $debug = true,
    ): ComponentPlugin {
        $cache = null;
        $resources = null;
        if ($cacheFile !== null) {
            $cache = new ResourceCheckerConfigCache($cacheFile, [
                new SelfCheckingResourceChecker(),
                new ArgumentResourceChecker($file),
                new EnvResourceChecker($this->envReader),
            ]);
            if (is_file($cache->getPath())
                && ($configuration = @include $cache->getPath()) instanceof ComponentPlugin
                && (!$debug || $cache->isFresh())) {
                return $configuration;
            }
            $resources = new ResourceCollection();
            $resources->addResource(new ArgumentResource($file));
            $resources->addClassResource(ComponentPlugin::class);
            $resources->addClassResource(VarExporter::class);
        }

        $paths = [];
        if (($cwd = getcwd()) !== false) {
            $paths[] = $cwd;
        }

        $loader = new ConfigurationLoader($resources);
        $locator = new FileLocator($paths);
        $fileLoader = new DelegatingLoader(new LoaderResolver([
            new YamlSymfonyFileLoader($loader, $locator),
            new YamlExtensionFileLoader($loader, $locator),
        ]));

        foreach ((array) $file as $path) {
            $fileLoader->load($path);
        }

        $configuration = ($this->compiledFactory ??= $this->compileFactory())
            ->process($loader->getConfigurations(), $resources);

        $cache?->write(
            class_exists(VarExporter::class)
                ? sprintf('<?php return %s;', VarExporter::export($configuration))
                : sprintf('<?php return unserialize(%s);', var_export(serialize($configuration), true)),
            $resources->toArray()
        );

        return $configuration;
    }

    private function compileFactory(): CompiledConfigurationFactory {
        $envReader = new TrackingEnvReader($this->envReader);
        $normalizations = [
            // Parse MUST perform environment variable substitution.
            new SubstitutionNormalization($envReader),
        ];

        $builder = new class extends NodeBuilder {
            public function node(?string $name, string $type): NodeDefinition {
                return parent::node($name, $type)->setPathSeparator("\0.");
            }
        };
        $builder->setNodeClass('variable', VariableNodeDefinition::class);
        $builder->setNodeClass('scalar', ScalarNodeDefinition::class);
        $builder->setNodeClass('boolean', BooleanNodeDefinition::class);
        $builder->setNodeClass('integer', IntegerNodeDefinition::class);
        $builder->setNodeClass('float', FloatNodeDefinition::class);
        $builder->setNodeClass('array', ArrayNodeDefinition::class);
        $builder->setNodeClass('enum', EnumNodeDefinition::class);
        $builder->setNodeClass('string', StringNodeDefinition::class);

        $registry = new ComponentProviderRegistry($normalizations, $builder);
        foreach ($this->componentProviders as $provider) {
            $registry->register($provider);
        }

        $node = $this->rootComponent
            ->getConfig($registry, $builder)
            ->getNode(forceRootNode: true);

        if ($node instanceof NormalizationsAware) {
            $node->setNormalizations($normalizations);
        }

        return new CompiledConfigurationFactory(
            $this->rootComponent,
            $node,
            [
                $registry,
                $envReader,
            ],
        );
    }
}
