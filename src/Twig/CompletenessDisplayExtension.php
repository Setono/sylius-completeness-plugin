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
            new TwigFunction('ssc_checker_label', [CompletenessDisplayRuntime::class, 'checkerLabel']),
        ];
    }
}
