<?php declare(strict_types=1);
namespace Nevay\OTelSDK\Configuration\Env\EnvLoaders\Trace;

use Nevay\OTelSDK\Configuration\Context;
use Nevay\OTelSDK\Configuration\Env\EnvResolver;
use Nevay\OTelSDK\Configuration\Env\Loader;
use Nevay\OTelSDK\Configuration\Env\LoaderRegistry;
use Nevay\OTelSDK\Trace\SpanExporter;
use Nevay\OTelSDK\Trace\SpanExporter\NoopSpanExporter;

/**
 * @implements Loader<SpanExporter>
 */
final class SpanExporterLoaderNone implements Loader {

    public function load(EnvResolver $env, LoaderRegistry $registry, Context $context): SpanExporter {
        return new NoopSpanExporter();
    }

    public function name(): string {
        return 'none';
    }
}
