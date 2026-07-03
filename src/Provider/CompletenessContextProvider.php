<?php

declare(strict_types=1);

namespace Setono\SyliusCompletenessPlugin\Provider;

use Setono\SyliusCompletenessPlugin\Model\CompletenessContextInterface;
use Setono\SyliusCompletenessPlugin\Repository\CompletenessContextRepositoryInterface;
use Symfony\Contracts\Service\ResetInterface;

final class CompletenessContextProvider implements CompletenessContextProviderInterface, ResetInterface
{
    /** @var array<string, CompletenessContextInterface>|null */
    private ?array $contexts = null;

    public function __construct(private readonly CompletenessContextRepositoryInterface $repository)
    {
    }

    public function getRollupWeight(string $channelCode, string $localeCode): float
    {
        return $this->getContext($channelCode, $localeCode)?->getRollupWeight() ?? 1.0;
    }

    public function getThreshold(string $channelCode, string $localeCode): ?int
    {
        return $this->getContext($channelCode, $localeCode)?->getThreshold();
    }

    public function reset(): void
    {
        $this->contexts = null;
    }

    private function getContext(string $channelCode, string $localeCode): ?CompletenessContextInterface
    {
        if (null === $this->contexts) {
            $this->contexts = [];
            foreach ($this->repository->findAll() as $context) {
                if (!$context instanceof CompletenessContextInterface) {
                    continue;
                }

                $this->contexts[$context->getChannelCode() . '|' . $context->getLocaleCode()] = $context;
            }
        }

        return $this->contexts[$channelCode . '|' . $localeCode] ?? null;
    }
}
