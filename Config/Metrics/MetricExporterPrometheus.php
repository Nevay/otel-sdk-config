<?php declare(strict_types=1);
namespace Nevay\OTelSDK\Configuration\Config\Metrics;

use Amp\Dns;
use Amp\Http\Server\SocketHttpServer;
use Amp\Socket\InternetAddress;
use Nevay\OTelSDK\Configuration\Config\ComponentProvider;
use Nevay\OTelSDK\Configuration\Config\ComponentProviderDependency;
use Nevay\OTelSDK\Configuration\Config\ComponentProviderRegistry;
use Nevay\OTelSDK\Configuration\Context;
use Nevay\OTelSDK\Metrics\MetricExporter;
use Nevay\OTelSDK\Prometheus\PrometheusMetricExporter;
use Nevay\SPI\ServiceProviderDependency\PackageDependency;
use Psr\Log\NullLogger;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;

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
     * } $properties
     */
    public function createPlugin(array $properties, Context $context): MetricExporter {
        $server = SocketHttpServer::createForDirectAccess($context->logger ?? new NullLogger(), allowedMethods: ['GET']);

        $host = $properties['host'];
        $port = $properties['port'];

        $address = Dns\resolve($host)[0]->getValue();
        $server->expose(new InternetAddress(
            address: $address,
            port: $port,
        ));

        return new PrometheusMetricExporter(
            server: $server,
            withoutUnits: $properties['without_units'],
            withoutTypeSuffix: $properties['without_type_suffix'],
            withoutScopeInfo: $properties['without_scope_info'],
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
