<?php declare(strict_types=1);
namespace Nevay\OTelSDK\Configuration\Config\Distribution;

use Nevay\OTelSDK\Configuration\Internal\File\FileWatcher;
use Nevay\OTelSDK\Configuration\Internal\File\InotifyFileWatcher;
use Nevay\SPI\ServiceProviderDependency\ExtensionDependency;
use OpenTelemetry\API\Configuration\Config\ComponentProvider;
use OpenTelemetry\API\Configuration\Config\ComponentProviderRegistry;
use OpenTelemetry\API\Configuration\Context;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\Definition\Builder\NodeBuilder;

/**
 * @implements ComponentProvider<FileWatcher>
 */
#[ExtensionDependency('inotify', '*')]
final class FileWatcherInotify implements ComponentProvider {

    /**
     * @param array{} $properties
     */
    public function createPlugin(array $properties, Context $context): FileWatcher {
        return new InotifyFileWatcher();
    }

    public function getConfig(ComponentProviderRegistry $registry, NodeBuilder $builder): ArrayNodeDefinition {
        return $builder->arrayNode('inotify');
    }
}
