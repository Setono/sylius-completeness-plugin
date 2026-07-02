<?php

declare(strict_types=1);

namespace Setono\SyliusCompletenessPlugin\Twig;

use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

final class CompletenessDisplayExtension extends AbstractExtension
{
    public function getFunctions(): array
    {
        return [
            new TwigFunction('ssc_rule_share', [CompletenessDisplayRuntime::class, 'ruleShare']),
            new TwigFunction('ssc_weight_share', [CompletenessDisplayRuntime::class, 'weightShare']),
            new TwigFunction('ssc_checker_label', [CompletenessDisplayRuntime::class, 'checkerLabel']),
            new TwigFunction('ssc_threshold_color', [CompletenessDisplayRuntime::class, 'thresholdColor']),
            new TwigFunction('ssc_is_stale', [CompletenessDisplayRuntime::class, 'isStale']),
            new TwigFunction('ssc_panel', [CompletenessDisplayRuntime::class, 'panel']),
            new TwigFunction('ssc_expression_functions', [CompletenessDisplayRuntime::class, 'expressionFunctions']),
            new TwigFunction('ssc_expression_function_docs', [CompletenessDisplayRuntime::class, 'expressionFunctionDocs']),
        ];
    }
}
