<?php declare(strict_types=1);
namespace Nevay\OTelSDK\Configuration\Config\Trace;

use Nevay\OTelSDK\Configuration\Internal\Util;
use Nevay\OTelSDK\Trace\Sampler\Composable\ComposableRuleBasedSampler;
use Nevay\OTelSDK\Trace\Sampler\Composable\ComposableSampler;
use Nevay\OTelSDK\Trace\Sampler\Composable\Predicate;
use Nevay\OTelSDK\Trace\Sampler\Composable\Predicate\AllPredicate;
use Nevay\OTelSDK\Trace\Sampler\Composable\Predicate\AnyPredicate;
use Nevay\OTelSDK\Trace\Sampler\Composable\Predicate\AttributePatternsPredicate;
use Nevay\OTelSDK\Trace\Sampler\Composable\Predicate\AttributeValuesPredicate;
use Nevay\OTelSDK\Trace\Sampler\Composable\Predicate\NotPredicate;
use Nevay\OTelSDK\Trace\Sampler\Composable\Predicate\ParentPredicate;
use Nevay\OTelSDK\Trace\Sampler\Composable\Predicate\SpanKindPredicate;
use Nevay\OTelSDK\Trace\Sampler\Composable\SamplingRule;
use Nevay\OTelSDK\Trace\Span\Kind;
use OpenTelemetry\API\Configuration\Config\ComponentPlugin;
use OpenTelemetry\API\Configuration\Config\ComponentProvider;
use OpenTelemetry\API\Configuration\Config\ComponentProviderRegistry;
use OpenTelemetry\API\Configuration\Context;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\Definition\Builder\NodeBuilder;
use function array_map;

/**
 * @implements ComponentProvider<ComposableSampler>
 */
final class ComposableSamplerRuleBased implements ComponentProvider {

    /**
     * @param array{
     *     rules: non-empty-list<array{
     *         attribute_values?: array{
     *             key: string,
     *             values: list<string>,
     *         },
     *         attribute_patterns?: array{
     *             key: string,
     *             included: ?list<string>,
     *             excluded: ?list<string>,
     *         },
     *         parent?: non-empty-list<'none'|'remote'|'local'>,
     *         span_kinds?: non-empty-list<'internal'|'server'|'client'|'producer'|'consumer'>,
     *         sampler: ComponentPlugin<ComposableSampler>,
     *     }>,
     * } $properties
     */
    public function createPlugin(array $properties, Context $context): ComposableSampler {
        $rules = [];
        foreach ($properties['rules'] as $rule) {
            $predicates = [];
            if ($rule['attribute_values'] ?? null) {
                $predicates[] = new AttributeValuesPredicate(
                    key: $rule['attribute_values']['key'],
                    values: $rule['attribute_values']['values'],
                );
            }
            if ($rule['attribute_patterns'] ?? null) {
                $predicates[] = new AttributePatternsPredicate(
                    key: $rule['attribute_patterns']['key'],
                    included: $rule['attribute_patterns']['included'] ?? '*',
                    excluded: $rule['attribute_patterns']['excluded'] ?? [],
                );
            }
            if ($rule['parent'] ?? null) {
                $predicates[] = new AnyPredicate(...array_map(
                    callback: static fn(string $parent): Predicate => match ($parent) {
                        'none' => new NotPredicate(new ParentPredicate()),
                        'remote' => new ParentPredicate(remote: true),
                        'local' => new ParentPredicate(remote: false),
                    },
                    array: $rule['parent'],
                ));
            }
            if ($rule['span_kinds'] ?? null) {
                $predicates[] = new SpanKindPredicate(...array_map(
                    callback: static fn(string $spanKind): Kind => match ($spanKind) {
                        'internal' => Kind::Internal,
                        'server' => Kind::Server,
                        'client' => Kind::Client,
                        'producer' => Kind::Producer,
                        'consumer' => Kind::Consumer,
                    },
                    array: $rule['span_kinds'],
                ));
            }

            $rules[] = new SamplingRule(
                predicate: new AllPredicate(...$predicates),
                sampler: $rule['sampler']->create($context),
            );
        }

        return new ComposableRuleBasedSampler(...$rules);
    }

    public function getConfig(ComponentProviderRegistry $registry, NodeBuilder $builder): ArrayNodeDefinition {
        $node = $builder->arrayNode('rule_based');
        $node
            ->children()
                ->arrayNode('rules')
                    ->arrayPrototype()
                        ->children()
                            ->arrayNode('attribute_values')
                                ->children()
                                    ->scalarNode('key')->isRequired()->validate()->always(Util::ensureString())->end()->end()
                                    ->arrayNode('values')->isRequired()->requiresAtLeastOneElement()->scalarPrototype()->validate()->always(Util::ensureString())->end()->end()->end()
                                ->end()
                            ->end()
                            ->arrayNode('attribute_patterns')
                                ->children()
                                    ->scalarNode('key')->isRequired()->validate()->always(Util::ensureString())->end()->end()
                                    ->arrayNode('included')->requiresAtLeastOneElement()->scalarPrototype()->validate()->always(Util::ensureString())->end()->end()->end()
                                    ->arrayNode('excluded')->requiresAtLeastOneElement()->scalarPrototype()->validate()->always(Util::ensureString())->end()->end()->end()
                                ->end()
                            ->end()
                            ->arrayNode('parent')->requiresAtLeastOneElement()->enumPrototype()->values(['none', 'remote', 'local'])->end()->end()
                            ->arrayNode('span_kinds')->requiresAtLeastOneElement()->enumPrototype()->values(['internal', 'server', 'client', 'producer', 'consumer'])->end()->end()
                            ->append($registry->component('sampler', ComposableSampler::class)->isRequired())
                        ->end()
                    ->end()
                ->end()
            ->end()
        ;

        return $node;
    }
}
