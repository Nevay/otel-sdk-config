<?php declare(strict_types=1);
namespace Nevay\OTelSDK\Configuration\Internal\Config\NodeDefinition;

use Nevay\OTelSDK\Configuration\Internal\Config\Node\IntegerNode;

/**
 * @internal
 */
final class IntegerNodeDefinition extends \Symfony\Component\Config\Definition\Builder\IntegerNodeDefinition {
    use NodeDefinitionTrait;

    protected function instantiateNode(): IntegerNode {
        return new IntegerNode($this->name, $this->parent, $this->min, $this->max, $this->pathSeparator);
    }
}
