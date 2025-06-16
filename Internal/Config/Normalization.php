<?php declare(strict_types=1);
namespace Nevay\OTelSDK\Configuration\Internal\Config;

use Symfony\Component\Config\Definition\NodeInterface;

/**
 * @internal
 */
interface Normalization {

    public function applyToNode(NodeInterface $node, mixed $value): mixed;
}
