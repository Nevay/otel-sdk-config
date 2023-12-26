<?php declare(strict_types=1);
namespace Nevay\OtelSDK\Configuration\Env\EnvLoaders\Propagator;

use Nevay\OtelSDK\Configuration\Context;
use Nevay\OtelSDK\Configuration\Env\EnvResolver;
use Nevay\OtelSDK\Configuration\Env\Loader;
use Nevay\OtelSDK\Configuration\Env\LoaderRegistry;
use OpenTelemetry\Context\Propagation\MultiTextMapPropagator;
use OpenTelemetry\Context\Propagation\TextMapPropagatorInterface;
use function array_unique;

/**
 * @implements Loader<TextMapPropagatorInterface>
 */
final class TextMapPropagatorLoaderPropagators implements Loader {

    public function load(EnvResolver $env, LoaderRegistry $registry, Context $context): TextMapPropagatorInterface {
        $propagators = [];
        foreach (array_unique($env->list('OTEL_PROPAGATORS') ?? ['tracecontext', 'baggage']) as $name) {
            $propagators[] = $registry->load(TextMapPropagatorInterface::class, $name, $env, $context);
        }

        return new MultiTextMapPropagator($propagators);
    }

    public function type(): string {
        return TextMapPropagatorInterface::class;
    }

    public function name(): string {
        return 'propagators';
    }

    public function dependencies(): array {
        return [];
    }
}
