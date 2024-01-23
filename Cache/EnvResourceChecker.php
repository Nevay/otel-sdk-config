<?php declare(strict_types=1);
namespace Nevay\OTelSDK\Configuration\Cache;

use Nevay\OTelSDK\Configuration\Env\EnvReader;
use Symfony\Component\Config\Resource\ResourceInterface;
use Symfony\Component\Config\ResourceCheckerInterface;
use function assert;

final class EnvResourceChecker implements ResourceCheckerInterface {

    public function __construct(
        private readonly EnvReader $envReader,
    ) {}

    public function supports(ResourceInterface $metadata): bool {
        return $metadata instanceof EnvResource;
    }

    public function isFresh(ResourceInterface $resource, int $timestamp): bool {
        assert($resource instanceof EnvResource);
        return $this->envReader->read($resource->name) === $resource->value;
    }
}
