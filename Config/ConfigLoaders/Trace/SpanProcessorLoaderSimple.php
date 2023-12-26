<?php declare(strict_types=1);
namespace Nevay\OtelSDK\Configuration\Config\ConfigLoaders\Trace;

use Nevay\OtelSDK\Configuration\Config\Loader;
use Nevay\OtelSDK\Configuration\Config\LoaderRegistry;
use Nevay\OtelSDK\Configuration\Context;
use Nevay\OtelSDK\Trace\SpanExporter;
use Nevay\OtelSDK\Trace\SpanProcessor;
use Nevay\OtelSDK\Trace\SpanProcessor\SimpleSpanProcessor;

/**
 * @implements Loader<SpanProcessor>
 */
final class SpanProcessorLoaderSimple implements Loader {

    public function load(array $config, LoaderRegistry $registry, Context $context): SpanProcessor {
        return new SimpleSpanProcessor(
            spanExporter: $registry->load(SpanExporter::class, $config['exporter'], $context),
            meterProvider: $context->meterProvider,
        );
    }

    public function type(): string {
        return SpanProcessor::class;
    }

    public function name(): string {
        return 'simple';
    }

    public function dependencies(): array {
        return [];
    }
}
