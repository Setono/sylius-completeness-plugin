<?php

declare(strict_types=1);

namespace Setono\SyliusCompletenessPlugin\Checker;

use Sylius\Component\Core\Model\ChannelInterface;
use Sylius\Component\Locale\Model\LocaleInterface;

/**
 * The (channel, locale) tuple a product is evaluated against
 */
final class CompletenessCheckContext
{
    private readonly string $channelCode;

    private readonly string $localeCode;

    public function __construct(
        private readonly ChannelInterface $channel,
        private readonly LocaleInterface $locale,
    ) {
        $channelCode = $channel->getCode();
        $localeCode = $locale->getCode();

        if (null === $channelCode || null === $localeCode) {
            throw new \InvalidArgumentException('Both the channel and the locale must have a code');
        }

        $this->channelCode = $channelCode;
        $this->localeCode = $localeCode;
    }

    public function getChannel(): ChannelInterface
    {
        return $this->channel;
    }

    public function getChannelCode(): string
    {
        return $this->channelCode;
    }

    public function getLocale(): LocaleInterface
    {
        return $this->locale;
    }

    public function getLocaleCode(): string
    {
        return $this->localeCode;
    }
}
