<?php declare(strict_types=1);
namespace Nevay\OTelSDK\Configuration\Internal\Config\NodeDefinition;

use Symfony\Component\Config\Definition\EnumNode;

/**
 * @internal
 */
final class EnumNodeDefinition extends \Symfony\Component\Config\Definition\Builder\EnumNodeDefinition {
    use NodeDefinitionTrait;

    protected function instantiateNode(): EnumNode {
        $node = parent::instantiateNode();

        return new EnumNode($this->name, $this->parent, $node->getValues(), $this->pathSeparator);
    }
}
