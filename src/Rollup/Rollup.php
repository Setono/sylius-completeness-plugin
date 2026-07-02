<?php

declare(strict_types=1);

namespace Setono\SyliusCompletenessPlugin\Rollup;

use Symfony\Contracts\Service\ServiceProviderInterface;

final class Rollup implements RollupInterface
{
    /**
     * @param ServiceProviderInterface<mixed> $strategies
     */
    public function __construct(
        private readonly ServiceProviderInterface $strategies,
        private readonly string $strategy,
    ) {
    }

    public function rollup(array $contextResults): ?int
    {
        $items = [];
        foreach ($contextResults as $contextResult) {
            if (null === $contextResult->ratio || $contextResult->excluded || $contextResult->rollupWeight <= 0.0) {
                continue;
            }

            $items[] = new RollupItem(
                channelCode: $contextResult->channelCode,
                localeCode: $contextResult->localeCode,
                ratio: $contextResult->ratio,
                weight: $contextResult->rollupWeight,
            );
        }

        if ([] === $items) {
            return null;
        }

        return $this->getStrategy()->rollup($items);
    }

    private function getStrategy(): RollupStrategyInterface
    {
        if (!$this->strategies->has($this->strategy)) {
            throw new \RuntimeException(sprintf(
                'The configured rollup strategy "%s" does not exist. Available strategies: %s',
                $this->strategy,
                implode(', ', array_keys($this->strategies->getProvidedServices())),
            ));
        }

        $strategy = $this->strategies->get($this->strategy);
        if (!$strategy instanceof RollupStrategyInterface) {
            throw new \RuntimeException(sprintf(
                'The rollup strategy "%s" does not implement %s',
                $this->strategy,
                RollupStrategyInterface::class,
            ));
        }

        return $strategy;
    }
}
