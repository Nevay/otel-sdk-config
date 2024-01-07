<?php declare(strict_types=1);
namespace Nevay\OtelSDK\Configuration\Config\Metrics;

use Amp\Dns;
use Amp\Http\Server\SocketHttpServer;
use Amp\Socket\InternetAddress;
use Nevay\OtelSDK\Configuration\Config\ComponentProvider;
use Nevay\OtelSDK\Configuration\Config\ComponentProviderDependency;
use Nevay\OtelSDK\Configuration\Config\ComponentProviderRegistry;
use Nevay\OtelSDK\Configuration\Context;
use Nevay\OtelSDK\Configuration\MetricExporterConfiguration;
use Nevay\OtelSDK\Metrics\AggregationResolvers;
use Nevay\OtelSDK\Metrics\TemporalityResolvers;
use Nevay\OtelSDK\Prometheus\PrometheusMetricExporter;
use Psr\Log\NullLogger;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use function Amp\Dns;

#[ComponentProviderDependency('tbachert/otel-sdk-prometheusexporter', '^0.1')]
#[ComponentProviderDependency('amphp/http-server', '^3.0')]
#[ComponentProviderDependency('amphp/socket', '^2.0')]
#[ComponentProviderDependency('amphp/dns', '^2.0')]
final class MetricExporterPrometheus implements ComponentProvider {

    /**
     * @param array{
     *     host: string,
     *     port: int,
     *     without_units: bool,
     *     without_type_suffix: bool,
     *     without_scope_info: bool,
     * } $properties
     */
    public function createPlugin(array $properties, Context $context): MetricExporterConfiguration {
        $server = SocketHttpServer::createForDirectAccess($context->logger ?? new NullLogger(), allowedMethods: ['GET']);

        $host = $properties['host'];
        $port = $properties['port'];

        $address = Dns\resolve($host)[0]->getValue();
        $server->expose(new InternetAddress(
            address: $address,
            port: $port,
        ));

        $exporter = new PrometheusMetricExporter(
            server: $server,
            withoutUnits: $properties['without_units'],
            withoutTypeSuffix: $properties['without_type_suffix'],
            withoutScopeInfo: $properties['without_scope_info'],
        );

        return new MetricExporterConfiguration(
            $exporter,
            TemporalityResolvers::Cumulative,
            AggregationResolvers::Default,
        );
    }

    public function getConfig(ComponentProviderRegistry $registry): ArrayNodeDefinition {
        $node = new ArrayNodeDefinition('prometheus');
        $node
            ->children()
                ->scalarNode('host')->defaultValue('localhost')->end()
                ->integerNode('port')->defaultValue(9464)->end()
                ->booleanNode('without_units')->defaultFalse()->end()
                ->booleanNode('without_type_suffix')->defaultFalse()->end()
                ->booleanNode('without_scope_info')->defaultFalse()->end()
            ->end()
        ;

        return $node;
    }
}
