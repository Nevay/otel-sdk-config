<?php declare(strict_types=1);
namespace Nevay\OTelSDK\Configuration\Env\EnvLoaders\Propagator;

use Nevay\OTelSDK\Configuration\Context;
use Nevay\OTelSDK\Configuration\Env\EnvResolver;
use Nevay\OTelSDK\Configuration\Env\Loader;
use Nevay\OTelSDK\Configuration\Env\LoaderRegistry;
use OpenTelemetry\Context\Propagation\MultiTextMapPropagator;
use OpenTelemetry\Context\Propagation\TextMapPropagatorInterface;
use function array_unique;

/**
 * @implements Loader<TextMapPropagatorInterface>
 */
final class TextMapPropagatorLoaderComposite implements Loader {

    public function load(EnvResolver $env, LoaderRegistry $registry, Context $context): TextMapPropagatorInterface {
        $propagators = [];
        foreach (array_unique($env->list('OTEL_PROPAGATORS') ?? ['tracecontext', 'baggage']) as $name) {
            $propagators[] = $registry->load(TextMapPropagatorInterface::class, $name, $env, $context);
        }

        return new MultiTextMapPropagator($propagators);
    }

    public function name(): string {
        return 'composite';
    }
}
