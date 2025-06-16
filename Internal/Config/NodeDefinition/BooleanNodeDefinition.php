<?php declare(strict_types=1);
namespace Nevay\OTelSDK\Configuration\Internal\Config\NodeDefinition;

use Nevay\OTelSDK\Configuration\Internal\Config\Node\BooleanNode;

/**
 * @internal
 */
final class BooleanNodeDefinition extends \Symfony\Component\Config\Definition\Builder\BooleanNodeDefinition {
    use NodeDefinitionTrait;

    protected function instantiateNode(): BooleanNode {
        return new BooleanNode($this->name, $this->parent, $this->pathSeparator);
    }
}
