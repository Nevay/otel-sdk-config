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
final class PersistingComponentPlugin implements ComponentPlugin {

    /**
     * @param ComponentPlugin<T> $plugin
     * @param T|null $slot
     *
     * @noinspection PhpPropertyOnlyWrittenInspection
     */
    public function __construct(
        private readonly ComponentPlugin $plugin,
        private mixed &$slot,
        private bool &$used,
    ) {}

    public function create(Context $context): mixed {
        assert(!$this->used);

        $object = $this->plugin->create($context);
        $this->slot = $object;
        $this->used = true;

        return $object;
    }
}
