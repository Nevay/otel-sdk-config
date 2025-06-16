<?php declare(strict_types=1);
namespace Nevay\OTelSDK\Configuration\Internal\Config;

/**
 * @internal
 */
interface ResourceTrackable {

    public function trackResources(?ResourceCollection $resources): void;
}
