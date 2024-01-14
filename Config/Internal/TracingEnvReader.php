<?php declare(strict_types=1);
namespace Nevay\OtelSDK\Configuration\Config\Internal;

use Nevay\OtelSDK\Configuration\Env\EnvReader;

/**
 * @internal
 */
final class TracingEnvReader implements EnvReader {

    private readonly EnvReader $envReader;
    private array $variables = [];

    public function __construct(EnvReader $envReader) {
        $this->envReader = $envReader;
    }

    public function read(string $name): ?string {
        return $this->variables[$name] ??= $this->envReader->read($name);
    }

    /**
     * @return iterable<string, string|null>
     */
    public function collect(): iterable {
        $variables = $this->variables;
        $this->variables = [];

        return $variables;
    }
}
