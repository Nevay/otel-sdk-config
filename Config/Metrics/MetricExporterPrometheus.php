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
     *     scope_info_enabled: bool,
     *     "target_info_enabled/development": bool,
     *     resource_constant_labels: array{
     *         included: ?list<string>,
     *         excluded: ?list<string>,
     *     },
     *     translation_strategy: 'underscore_escaping_with_suffixes'|'underscore_escaping_without_suffixes/development'|'no_utf8_escaping_with_suffixes/development'|'no_translation/development',
     * } $properties
     */
    public function createPlugin(array $properties, Context $context): MetricExporter {
        $server = new SocketHttpServer(
            $context->logger,
            new UnreferencedServerSocketFactory(),
            new SocketClientFactory($context->logger),
        );

        $host = $properties['host'];
        $port = $properties['port'];

        foreach (Dns\resolve($host) as $dnsRecord) {
            $server->expose(new InternetAddress(
                address: $dnsRecord->getValue(),
                port: $port,
            ));
        }

        return new PrometheusMetricExporter(
            server: $server,
            scopeInfoEnabled: $properties['scope_info_enabled'],
            targetInfoEnabled: $properties['target_info_enabled/development'],
            resourceConstantLabels: Attributes::filterKeys(
                include: $properties['resource_constant_labels']['included'] ?? [],
                exclude: $properties['resource_constant_labels']['excluded'] ?? [],
            ),
            translationStrategy: match ($properties['translation_strategy']) {
                'underscore_escaping_with_suffixes' => TranslationStrategy::UnderscoreEscapingWithSuffixes,
                'underscore_escaping_without_suffixes/development' => TranslationStrategy::UnderscoreEscapingWithoutSuffixes,
                'no_utf8_escaping_with_suffixes/development' => TranslationStrategy::NoUTF8EscapingWithSuffixes,
                'no_translation/development' => TranslationStrategy::NoTranslation,
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
                ->booleanNode('scope_info_enabled')->defaultTrue()->end()
                ->booleanNode('target_info_enabled/development')->defaultTrue()->end()
                ->arrayNode('resource_constant_labels')
                    ->children()
                        ->arrayNode('included')->defaultNull()->scalarPrototype()->validate()->always(Util::ensureString())->end()->end()->end()
                        ->arrayNode('excluded')->defaultNull()->scalarPrototype()->validate()->always(Util::ensureString())->end()->end()->end()
                    ->end()
                ->end()
                ->enumNode('translation_strategy')
                    ->defaultValue('underscore_escaping_with_suffixes')
                    ->values([
                        'underscore_escaping_with_suffixes',
                        'underscore_escaping_without_suffixes/development',
                        'no_utf8_escaping_with_suffixes/development',
                        'no_translation/development',
                    ])
                ->end()
            ->end()
        ;

        return $node;
    }
}
