<?php declare(strict_types=1);
namespace Nevay\OTelSDK\Configuration\Internal\Config\NodeDefinition;

use Nevay\OTelSDK\Configuration\Internal\Config\Node\ArrayNode;
use Nevay\OTelSDK\Configuration\Internal\Config\Node\PrototypedArrayNode;
use Symfony\Component\Config\Definition\Builder\NodeParentInterface;
use Symfony\Component\Config\Definition\NodeInterface;

/**
 * @internal
 */
final class ArrayNodeDefinition extends \Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition {
    use NodeDefinitionTrait;

    private bool $defaultValueSet = false;

    public function __construct(?string $name, ?NodeParentInterface $parent = null) {
        parent::__construct($name, $parent);

        $this->nullEquivalent = null;
    }

    protected function createNode(): NodeInterface {
        $node = parent::createNode();

        $node = match (true) {
            $node instanceof \Symfony\Component\Config\Definition\PrototypedArrayNode => PrototypedArrayNode::fromNode($node),
            $node instanceof \Symfony\Component\Config\Definition\ArrayNode => ArrayNode::fromNode($node),
        };

        $node->setAllowEmptyValue($this->allowEmptyValue);
        if ($this->defaultValueSet) {
            $node->setDefaultValue($this->defaultValue);
        }


        return $node;
    }

    public function defaultValue(mixed $value): static {
        $this->defaultValueSet = true;
        $this->defaultValue = $value;

        return $this;
    }
}
