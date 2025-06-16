<?php declare(strict_types=1);
namespace Nevay\OTelSDK\Configuration\Internal\Config;

use Symfony\Component\Config\Resource\ResourceInterface;

/**
 * @internal
 */
class EnvResource implements ResourceInterface {

    public function __construct(
        public readonly string $name,
        public readonly ?string $value,
    ) {}

    public function __toString(): string {
        return 'env.' . $this->name;
    }
}
