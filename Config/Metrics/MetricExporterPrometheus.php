<?php declare(strict_types=1);
namespace Nevay\OTelSDK\Configuration\Config\Metrics;

use Amp\Dns;
use Amp\Http\Server\DefaultErrorHandler;
use Amp\Http\Server\Driver\SocketClientFactory;
use Amp\Http\Server\SocketHttpServer;
use Amp\Socket\InternetAddress;
use Nevay\OTelSDK\Common\Attributes;
use Nevay\OTelSDK\Configuration\ComponentProvider;
use Nevay\OTelSDK\Configuration\ComponentProviderRegistry;
use Nevay\OTelSDK\Configuration\Context;
use Nevay\OTelSDK\Configuration\Validation;
use Nevay\OTelSDK\Metrics\MetricExporter;
use Nevay\OTelSDK\Prometheus\Internal\HttpServer\HttpServerClosable;
use Nevay\OTelSDK\Prometheus\Internal\Socket\UnreferencedServerSocketFactory;
use Nevay\OTelSDK\Prometheus\PrometheusMetricExporter;
use Nevay\SPI\ServiceProviderDependency\PackageDependency;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\Definition\Builder\NodeBuilder;

#[PackageDependency('tbachert/otel-sdk-prometheusexporter', '^0.1')]
#[PackageDependency('amphp/http-server', '^3.0')]
#[PackageDependency('amphp/socket', '^2.0')]
#[PackageDependency('amphp/dns', '^2.0')]
final class MetricExporterPrometheus implements ComponentProvider {

    /**
     * @param array{
     *     host: string,
     *     port: int,
     *     without_units: bool,
     *     without_type_suffix: bool,
     *     without_scope_info: bool,
     *     with_resource_constant_labels: array{
     *         included: ?list<string>,
     *         excluded: ?list<string>,
     *     },
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

        $exporter = new PrometheusMetricExporter(
            server: new HttpServerClosable($server),
            withoutUnits: $properties['without_units'],
            withoutTypeSuffix: $properties['without_type_suffix'],
            withoutScopeInfo: $properties['without_scope_info'],
            withResourceConstantLabels: Attributes::filterKeys(
                include: $properties['with_resource_constant_labels']['included'] ?? [],
                exclude: $properties['with_resource_constant_labels']['excluded'] ?? [],
            ),
            logger: $context->logger,
        );
        $server->start($exporter, new DefaultErrorHandler());

        return $exporter;
    }

    public function getConfig(ComponentProviderRegistry $registry, NodeBuilder $builder): ArrayNodeDefinition {
        $node = $builder->arrayNode('prometheus/development');
        $node
            ->children()
                ->scalarNode('host')->defaultValue('localhost')->validate()->always(Validation::ensureString())->end()->end()
                ->integerNode('port')->defaultValue(9464)->end()
                ->booleanNode('without_units')->defaultFalse()->end()
                ->booleanNode('without_type_suffix')->defaultFalse()->end()
                ->booleanNode('without_scope_info')->defaultFalse()->end()
                ->arrayNode('with_resource_constant_labels')
                    ->children()
                        ->arrayNode('included')->defaultNull()->scalarPrototype()->validate()->always(Validation::ensureString())->end()->end()->end()
                        ->arrayNode('excluded')->defaultNull()->scalarPrototype()->validate()->always(Validation::ensureString())->end()->end()->end()
                    ->end()
                ->end()
            ->end()
        ;

        return $node;
    }
}
