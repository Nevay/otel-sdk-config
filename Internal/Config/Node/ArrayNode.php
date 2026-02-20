<?php declare(strict_types=1);
namespace Nevay\OTelSDK\Configuration\Internal\Config\Node;

use Nevay\OTelSDK\Configuration\Internal\Config\Normalization;
use Nevay\OTelSDK\Configuration\Internal\Config\NormalizationsAware;
use function get_object_vars;

/**
 * @internal
 */
final class ArrayNode extends \Symfony\Component\Config\Definition\ArrayNode implements NormalizationsAware {
    use NodeTrait;

    /** @var list<Normalization> */
    private array $normalizations = [];

    private bool $defaultValueSet = false;
    private mixed $defaultValue = null;
    private bool $allowEmptyValue = true;

    public static function fromNode(\Symfony\Component\Config\Definition\ArrayNode $node): ArrayNode {
        $_node = new self($node->name, $node->parent, $node->pathSeparator);
        foreach (get_object_vars($node) as $property => $value) {
            $_node->$property = $value;
        }

        return $_node;
    }

    public function setNormalizations(array $normalizations): void {
        $this->normalizations = $normalizations;
    }

    protected function preNormalize(mixed $value): mixed {
        if ($value === null && $this->allowEmptyValue && $this->hasDefaultValue()) {
            $value = $this->getDefaultValue();
        }

        foreach ($this->normalizations as $normalization) {
            $value = $normalization->applyToNode($this, $value);
        }

        return $value;
    }

    public function setDefaultValue(mixed $value): void {
        $this->defaultValue = $value;
        $this->defaultValueSet = true;
    }

    public function hasDefaultValue(): bool {
        return $this->defaultValueSet || parent::hasDefaultValue();
    }

    public function getDefaultValue(): mixed {
        return $this->defaultValueSet
            ? $this->defaultValue
            : parent::getDefaultValue();
    }

    public function setAllowEmptyValue(bool $boolean): void {
        $this->allowEmptyValue = $boolean;
    }
}
