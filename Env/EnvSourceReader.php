<?php declare(strict_types=1);
namespace Nevay\OtelSDK\Configuration\Env;

use function trim;

final class EnvSourceReader implements EnvReader {

    /**
     * @param iterable<EnvSource> $envSources
     */
    public function __construct(
        private readonly iterable $envSources,
    ) {}

    public function read(string $name): ?string {
        foreach ($this->envSources as $envSource) {
            if (($value = $envSource->raw($name)) !== null && ($value = trim($value, " \t")) !== '') {
                return $value;
            }
        }

        return null;
    }
}
