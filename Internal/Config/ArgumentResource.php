<?php declare(strict_types=1);
namespace Nevay\OTelSDK\Configuration\Internal\Config;

use Symfony\Component\Config\Resource\ResourceInterface;
use function serialize;

/**
 * @internal
 */
final class ArgumentResource implements ResourceInterface {

    public function __construct(
        public readonly mixed $value,
    ) {}

    public function __toString(): string {
        return serialize($this->value);
    }
}
