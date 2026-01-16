<?php declare(strict_types=1);
namespace Nevay\OTelSDK\Configuration\ConfigEnv\ResponsePropagator;

use Nevay\SPI\ServiceProviderDependency\PackageDependency;
use OpenTelemetry\API\Configuration\ConfigEnv\EnvComponentLoader;
use OpenTelemetry\API\Configuration\ConfigEnv\EnvComponentLoaderRegistry;
use OpenTelemetry\API\Configuration\ConfigEnv\EnvResolver;
use OpenTelemetry\API\Configuration\Context;
use OpenTelemetry\Context\Propagation\ResponsePropagatorInterface;
use OpenTelemetry\Contrib\Propagation\ServerTiming\ServerTimingPropagator;

/**
 * @implements EnvComponentLoader<ResponsePropagatorInterface>
 */
#[PackageDependency('open-telemetry/opentelemetry-propagation-server-timing', '^0.2')]
final class ResponsePropagatorLoaderServerTiming implements EnvComponentLoader {

    public function load(EnvResolver $env, EnvComponentLoaderRegistry $registry, Context $context): ResponsePropagatorInterface {
        return ServerTimingPropagator::getInstance();
    }

    public function name(): string {
        return 'servertiming';
    }
}
