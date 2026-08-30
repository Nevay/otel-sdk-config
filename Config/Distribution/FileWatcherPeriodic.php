<?php declare(strict_types=1);
namespace Nevay\OTelSDK\Configuration\Config\Distribution;

use Nevay\OTelSDK\Configuration\Internal\File\FileWatcher;
use Nevay\OTelSDK\Configuration\Internal\File\PeriodicFileWatcher;
use OpenTelemetry\API\Configuration\Config\ComponentProvider;
use OpenTelemetry\API\Configuration\Config\ComponentProviderRegistry;
use OpenTelemetry\API\Configuration\Context;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\Definition\Builder\NodeBuilder;

/**
 * @implements ComponentProvider<FileWatcher>
 */
final class FileWatcherPeriodic implements ComponentProvider {

    /**
     * @param array{
     *     interval: float,
     * } $properties
     */
    public function createPlugin(array $properties, Context $context): FileWatcher {
        return new PeriodicFileWatcher($properties['interval']);
    }

    public function getConfig(ComponentProviderRegistry $registry, NodeBuilder $builder): ArrayNodeDefinition {
        $node = $builder->arrayNode('periodic');
        $node
            ->children()
                ->floatNode('interval')->isRequired()->min(0)->end()
            ->end()
        ;

        return $node;
    }
}
