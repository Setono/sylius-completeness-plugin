<?php

declare(strict_types=1);

namespace Setono\SyliusCompletenessPlugin\Grid\Filter;

use Sylius\Component\Grid\Data\DataSourceInterface;
use Sylius\Component\Grid\Filtering\FilterInterface;

/**
 * Filters rules by a code contained in one of the JSON scope columns (channelCodes / localeCodes).
 *
 * The scope is persisted as a JSON array of codes, so we match membership with a LIKE on the
 * serialized value (e.g. `%"WEB"%`). The quotes make it an exact-code match rather than a prefix one.
 */
final class ScopeCodeFilter implements FilterInterface
{
    /**
     * @param mixed $data
     * @param array<array-key, mixed> $options
     */
    public function apply(DataSourceInterface $dataSource, string $name, $data, array $options): void
    {
        if (null === $data || '' === $data) {
            return;
        }

        $field = isset($options['field']) && is_string($options['field']) ? $options['field'] : $name;

        $codes = array_filter(
            is_array($data) ? $data : [$data],
            static fn ($code): bool => is_string($code) && '' !== $code,
        );

        if ([] === $codes) {
            return;
        }

        $expressionBuilder = $dataSource->getExpressionBuilder();

        $expressions = [];
        foreach ($codes as $code) {
            $expressions[] = $expressionBuilder->like($field, sprintf('%%"%s"%%', $code));
        }

        $dataSource->restrict(1 === count($expressions) ? $expressions[0] : $expressionBuilder->orX(...$expressions));
    }
}
