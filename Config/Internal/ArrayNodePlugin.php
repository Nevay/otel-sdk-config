<?php declare(strict_types=1);
namespace Nevay\OTelSDK\Configuration\Config\Internal;

use Symfony\Component\Config\Definition\ArrayNode;
use function get_object_vars;

/**
 * @internal
 */
final class ArrayNodePlugin extends ArrayNode {

    public static function fromNode(ArrayNode $node): ArrayNodePlugin {
        $pluginNode = new ArrayNodePlugin($node->getName());
        foreach (get_object_vars($node) as $property => $value) {
            $pluginNode->$property = $value;
        }

        return $pluginNode;
    }

    public function hasDefaultValue(): bool {
        return true;
    }

    public function getDefaultValue(): mixed {
        return null;
    }
}
