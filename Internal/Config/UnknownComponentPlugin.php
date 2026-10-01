<?php declare(strict_types=1);
namespace Nevay\OTelSDK\Configuration\Internal\Config;

use InvalidArgumentException;
use OpenTelemetry\API\Configuration\Config\ComponentPlugin;
use OpenTelemetry\API\Configuration\Context;
use function array_map;
use function implode;
use function sprintf;

/**
 * @internal
 */
final class UnknownComponentPlugin implements ComponentPlugin {

    public function __construct(
        private readonly string $type,
        private readonly string $unknownProvider,
        private readonly array $availableProviders,
    ) {}

    public function create(Context $context): never {
        throw new InvalidArgumentException(sprintf('Component "%s" uses unknown provider "%s", available providers are %s',
            $this->type, $this->unknownProvider, implode(', ', array_map(json_encode(...), $this->availableProviders ?: ['none']))));
    }
}
