<?php declare(strict_types=1);
namespace Nevay\OTelSDK\Configuration\Internal\Config\NodeDefinition;

/**
 * @internal
 */
trait NodeDefinitionTrait {

    public function cannotBeEmpty(): static {
        $this->allowEmptyValue = false;

        return $this;
    }
}
