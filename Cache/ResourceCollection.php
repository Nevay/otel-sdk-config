<?php declare(strict_types=1);
namespace Nevay\OtelSDK\Configuration\Cache;

use Symfony\Component\Config\Resource\ResourceInterface;

interface ResourceCollection {

    public function add(ResourceInterface $resource): void;
}
