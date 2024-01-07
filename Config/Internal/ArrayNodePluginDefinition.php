<?php declare(strict_types=1);
namespace Nevay\OtelSDK\Configuration\Config\Internal;

use Symfony\Component\Config\Definition\ArrayNode;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\Definition\NodeInterface;
use function assert;
use function get_object_vars;

/**
 * @internal
 */
final class ArrayNodePluginDefinition extends ArrayNodeDefinition {

    public static function fromNodeDefinition(ArrayNodeDefinition $node): ArrayNodePluginDefinition {
        $pluginNode = new ArrayNodePluginDefinition($node->name);
        foreach (get_object_vars($node) as $property => $value) {
            $pluginNode->$property = $value;
        }

        return $pluginNode;
    }

    protected function createNode(): NodeInterface {
        $node = parent::createNode();
        assert($node instanceof ArrayNode);

        return ArrayNodePlugin::fromNode($node);
    }
}
