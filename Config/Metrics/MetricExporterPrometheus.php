<?php declare(strict_types=1);
namespace Nevay\OTelSDK\Configuration\Config\Metrics;

use Amp\Dns;
use Amp\Http\Server\Driver\SocketClientFactory;
use Amp\Http\Server\SocketHttpServer;
use Amp\Socket\InternetAddress;
use Nevay\OTelSDK\Common\Attributes;
use Nevay\OTelSDK\Configuration\Internal\Util;
use Nevay\OTelSDK\Metrics\MetricExporter;
use Nevay\OTelSDK\Prometheus\Internal\Socket\UnreferencedServerSocketFactory;
use Nevay\OTelSDK\Prometheus\PrometheusMetricExporter;
use Nevay\OTelSDK\Prometheus\TranslationStrategy;
use Nevay\SPI\ServiceProviderDependency\PackageDependency;
use OpenTelemetry\API\Configuration\Config\ComponentProvider;
use OpenTelemetry\API\Configuration\Config\ComponentProviderRegistry;
use OpenTelemetry\API\Configuration\Context;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\Definition\Builder\NodeBuilder;

/**
 * @implements ComponentProvider<MetricExporter>
 */
#[PackageDependency('tbachert/otel-sdk-prometheusexporter', '^0.1')]
#[PackageDependency('amphp/http-server', '^3.0')]
#[PackageDependency('amphp/socket', '^2.0')]
#[PackageDependency('amphp/dns', '^2.0')]
final class MetricExporterPrometheus implements ComponentProvider {

    /**
     * @param array{
     *     host: string,
     *     port: int,
     *     without_scope_info: bool,
     *     without_target_info: bool,
     *     with_resource_constant_labels: array{
     *         included: ?list<string>,
     *         excluded: ?list<string>,
     *     },
     *     translation_strategy: 'underscore_escaping_with_suffixes'|'underscore_escaping_without_suffixes'|'no_utf8_escaping_with_suffixes'|'no_translation',
     * } $properties
     */
    public function createPlugin(array $properties, Context $context): MetricExporter {
        $server = new SocketHttpServer(
            $context->logger,
            new UnreferencedServerSocketFactory(),
            new SocketClientFactory($context->logger),
            allowedMethods: ['GET'],
        );

        $host = $properties['host'];
        $port = $properties['port'];

        $address = Dns\resolve($host)[0]->getValue();
        $server->expose(new InternetAddress(
            address: $address,
            port: $port,
        ));

        return new PrometheusMetricExporter(
            server: $server,
            withoutScopeInfo: $properties['without_scope_info'],
            withoutTargetInfo: $properties['without_target_info'],
            withResourceConstantLabels: Attributes::filterKeys(
                include: $properties['with_resource_constant_labels']['included'] ?? [],
                exclude: $properties['with_resource_constant_labels']['excluded'] ?? [],
            ),
            translationStrategy: match ($properties['translation_strategy']) {
                'underscore_escaping_with_suffixes' => TranslationStrategy::UnderscoreEscapingWithSuffixes,
                'underscore_escaping_without_suffixes' => TranslationStrategy::UnderscoreEscapingWithoutSuffixes,
                'no_utf8_escaping_with_suffixes' => TranslationStrategy::NoUTF8EscapingWithSuffixes,
                'no_translation' => TranslationStrategy::NoTranslation,
            },
            logger: $context->logger,
        );
    }

    public function getConfig(ComponentProviderRegistry $registry, NodeBuilder $builder): ArrayNodeDefinition {
        $node = $builder->arrayNode('prometheus/development');
        $node
            ->children()
                ->scalarNode('host')->defaultValue('localhost')->validate()->always(Util::ensureString())->end()->end()
                ->integerNode('port')->defaultValue(9464)->end()
                ->booleanNode('without_scope_info')->defaultFalse()->end()
                ->booleanNode('without_target_info')->defaultFalse()->end()
                ->arrayNode('with_resource_constant_labels')
                    ->children()
                        ->arrayNode('included')->defaultNull()->scalarPrototype()->validate()->always(Util::ensureString())->end()->end()->end()
                        ->arrayNode('excluded')->defaultNull()->scalarPrototype()->validate()->always(Util::ensureString())->end()->end()->end()
                    ->end()
                ->end()
                ->enumNode('translation_strategy')
                    ->defaultValue('underscore_escaping_with_suffixes')
                    ->values([
                        'underscore_escaping_with_suffixes',
                        'underscore_escaping_without_suffixes',
                        'no_utf8_escaping_with_suffixes',
                        'no_translation',
                    ])
                ->end()
            ->end()
        ;

        return $node;
    }
}
