<?php declare(strict_types=1);
namespace Nevay\OTelSDK\Configuration\Internal\Config;

use OpenTelemetry\API\Configuration\Config\ComponentPlugin;
use OpenTelemetry\API\Configuration\Context;
use function assert;

/**
 * @template T
 * @implements ComponentPlugin<T>
 *
 * @internal
 */
final class ReusedPersistentComponentPlugin implements ComponentPlugin {

    public function __construct(
        private readonly mixed $instance,
        private bool &$used,
    ) {}

    public function create(Context $context): mixed {
        assert(!$this->used);

        $this->used = true;

        return $this->instance;
    }
}

