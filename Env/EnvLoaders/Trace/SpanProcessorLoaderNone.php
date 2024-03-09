<?php declare(strict_types=1);
namespace Nevay\OTelSDK\Configuration\Env\EnvLoaders\Trace;

use Nevay\OTelSDK\Configuration\Context;
use Nevay\OTelSDK\Configuration\Env\EnvResolver;
use Nevay\OTelSDK\Configuration\Env\Loader;
use Nevay\OTelSDK\Configuration\Env\LoaderRegistry;
use Nevay\OTelSDK\Trace\SpanProcessor;
use Nevay\OTelSDK\Trace\SpanProcessor\NoopSpanProcessor;

/**
 * @implements Loader<SpanProcessor>
 */
final class SpanProcessorLoaderNone implements Loader {

    public function load(EnvResolver $env, LoaderRegistry $registry, Context $context): SpanProcessor {
        return new NoopSpanProcessor();
    }

    public function name(): string {
        return 'none';
    }
}
