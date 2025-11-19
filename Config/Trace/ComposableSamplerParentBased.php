<?php declare(strict_types=1);
namespace Nevay\OTelSDK\Configuration\Config\Trace;

use Nevay\OTelSDK\Trace\Sampler\Composable\ComposableParentThresholdSampler;
use Nevay\OTelSDK\Trace\Sampler\Composable\ComposableRuleBasedSampler;
use Nevay\OTelSDK\Trace\Sampler\Composable\ComposableSampler;
use Nevay\OTelSDK\Trace\Sampler\Composable\Predicate\ParentPredicate;
use Nevay\OTelSDK\Trace\Sampler\Composable\Predicate\TruePredicate;
use Nevay\OTelSDK\Trace\Sampler\Composable\SamplingRule;
use OpenTelemetry\API\Configuration\Config\ComponentPlugin;
use OpenTelemetry\API\Configuration\Config\ComponentProvider;
use OpenTelemetry\API\Configuration\Config\ComponentProviderRegistry;
use OpenTelemetry\API\Configuration\Context;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\Definition\Builder\NodeBuilder;

/**
 * @implements ComponentProvider<ComposableSampler>
 */
final class ComposableSamplerParentBased implements ComponentProvider {

    /**
     * @param array{
     *     root: ComponentPlugin<ComposableSampler>,
     *     remote_parent_sampled: ?ComponentPlugin<ComposableSampler>,
     *     remote_parent_not_sampled: ?ComponentPlugin<ComposableSampler>,
     *     local_parent_sampled: ?ComponentPlugin<ComposableSampler>,
     *     local_parent_not_sampled: ?ComponentPlugin<ComposableSampler>,
     * } $properties
     */
    public function createPlugin(array $properties, Context $context): ComposableSampler {
        $rules = [];
        if ($properties['remote_parent_sampled']) {
            $rules[] = new SamplingRule(new ParentPredicate(remote: true, sampled: true), $properties['remote_parent_sampled']->create($context));
        }
        if ($properties['remote_parent_not_sampled']) {
            $rules[] = new SamplingRule(new ParentPredicate(remote: true, sampled: false), $properties['remote_parent_not_sampled']->create($context));
        }
        if ($properties['local_parent_sampled']) {
            $rules[] = new SamplingRule(new ParentPredicate(remote: false, sampled: true), $properties['local_parent_sampled']->create($context));
        }
        if ($properties['local_parent_not_sampled']) {
            $rules[] = new SamplingRule(new ParentPredicate(remote: false, sampled: false), $properties['local_parent_not_sampled']->create($context));
        }

        $root = new ComposableParentThresholdSampler($properties['root']->create($context));

        if (!$rules) {
            return $root;
        }

        $rules[] = new SamplingRule(new TruePredicate(), $root);

        return new ComposableRuleBasedSampler(...$rules);
    }

    public function getConfig(ComponentProviderRegistry $registry, NodeBuilder $builder): ArrayNodeDefinition {
        $node = $builder->arrayNode('parent_based');
        $node
            ->children()
                ->append($registry->component('root', ComposableSampler::class)->isRequired())
                ->append($registry->component('remote_parent_sampled', ComposableSampler::class))
                ->append($registry->component('remote_parent_not_sampled', ComposableSampler::class))
                ->append($registry->component('local_parent_sampled', ComposableSampler::class))
                ->append($registry->component('local_parent_not_sampled', ComposableSampler::class))
            ->end()
        ;

        return $node;
    }
}
