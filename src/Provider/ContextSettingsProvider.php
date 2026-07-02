<?php

declare(strict_types=1);

namespace Setono\SyliusCompletenessPlugin\Provider;

use Setono\SyliusCompletenessPlugin\Model\CompletenessContextSettingInterface;
use Setono\SyliusCompletenessPlugin\Repository\CompletenessContextSettingRepositoryInterface;
use Symfony\Contracts\Service\ResetInterface;

final class ContextSettingsProvider implements ContextSettingsProviderInterface, ResetInterface
{
    /** @var array<string, CompletenessContextSettingInterface>|null */
    private ?array $settings = null;

    public function __construct(private readonly CompletenessContextSettingRepositoryInterface $repository)
    {
    }

    public function getRollupWeight(string $channelCode, string $localeCode): float
    {
        return $this->getSetting($channelCode, $localeCode)?->getRollupWeight() ?? 1.0;
    }

    public function getThreshold(string $channelCode, string $localeCode): ?int
    {
        return $this->getSetting($channelCode, $localeCode)?->getThreshold();
    }

    public function reset(): void
    {
        $this->settings = null;
    }

    private function getSetting(string $channelCode, string $localeCode): ?CompletenessContextSettingInterface
    {
        if (null === $this->settings) {
            $this->settings = [];
            foreach ($this->repository->findAll() as $setting) {
                if (!$setting instanceof CompletenessContextSettingInterface) {
                    continue;
                }

                $this->settings[$setting->getChannelCode() . '|' . $setting->getLocaleCode()] = $setting;
            }
        }

        return $this->settings[$channelCode . '|' . $localeCode] ?? null;
    }
}
