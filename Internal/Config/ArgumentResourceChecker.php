<?php declare(strict_types=1);
namespace Nevay\OTelSDK\Configuration\Internal\Config;

use Symfony\Component\Config\Resource\ResourceInterface;
use Symfony\Component\Config\ResourceCheckerInterface;
use function assert;

/**
 * @internal
 */
final class ArgumentResourceChecker implements ResourceCheckerInterface {

    public function __construct(
        private readonly mixed $value,
    ) {}

    public function supports(ResourceInterface $metadata): bool {
        return $metadata instanceof ArgumentResource;
    }

    public function isFresh(ResourceInterface $resource, int $timestamp): bool {
        assert($resource instanceof ArgumentResource);
        return $resource->value === $this->value;
    }
}
