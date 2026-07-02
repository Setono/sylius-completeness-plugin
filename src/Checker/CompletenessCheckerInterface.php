<?php

declare(strict_types=1);

namespace Setono\SyliusCompletenessPlugin\Checker;

use Sylius\Component\Core\Model\ProductInterface;

interface CompletenessCheckerInterface
{
    /**
     * Returns the stable type code for this checker, e.g. 'has_image'
     */
    public static function getType(): string;

    /**
     * Returns the group this checker belongs to, used to organise the checker dropdown into optgroups,
     * e.g. 'content', 'media', 'seo'. Return null to place the checker in the "Misc" group
     */
    public static function getGroup(): ?string;

    /**
     * Returns a score between 0.0 (not met) and 1.0 (fully met). Binary checkers return exactly 0.0 or 1.0.
     * The calculator clamps out-of-range numbers to [0, 1]
     *
     * @param array<string, mixed> $configuration
     *
     * @throws \Throwable when the check cannot be performed. The calculator treats a throwing checker as the
     *                    "errored" state: the rule stays in the denominator, contributes no credit and the
     *                    error message is surfaced in the breakdown and preview
     */
    public function score(ProductInterface $product, CompletenessCheckContext $context, array $configuration): float;
}
