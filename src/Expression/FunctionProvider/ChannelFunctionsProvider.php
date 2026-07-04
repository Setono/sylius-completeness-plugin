<?php

declare(strict_types=1);

namespace Setono\SyliusCompletenessPlugin\Expression\FunctionProvider;

use Setono\SyliusCompletenessPlugin\Context\CalculationContextInterface;
use Setono\SyliusCompletenessPlugin\Util\Pricing;
use Sylius\Component\Core\Model\ProductInterface;

final class ChannelFunctionsProvider extends FunctionProvider
{
    public function __construct(private readonly CalculationContextInterface $calculationContext)
    {
    }

    public function getFunctions(): array
    {
        return [
            $this->createFunction(
                'is_enabled',
                'is_enabled(product): bool',
                'True when the product is enabled.',
                fn (array $variables, mixed $product): bool => $this->assertProduct($product, 'is_enabled')->isEnabled(),
            ),
            $this->createFunction(
                'is_in_channel',
                'is_in_channel(product[, channelCode]): bool',
                'True when the product is assigned to the channel.',
                function (array $variables, mixed $product, mixed $channelCode = null): bool {
                    $product = $this->assertProduct($product, 'is_in_channel');

                    return self::isInChannel($product, $this->resolveChannelCode($channelCode));
                },
            ),
            $this->createFunction(
                'channel_count',
                'channel_count(product): int',
                'The number of channels the product is assigned to.',
                fn (array $variables, mixed $product): int => $this->assertProduct($product, 'channel_count')->getChannels()->count(),
            ),
            $this->createFunction(
                'has_price',
                'has_price(product[, channelCode]): bool',
                'True when at least one enabled variant is priced in the channel.',
                function (array $variables, mixed $product, mixed $channelCode = null): bool {
                    $product = $this->assertProduct($product, 'has_price');

                    return Pricing::hasPriceInChannel($product, $this->resolveChannelCode($channelCode));
                },
            ),
            $this->createFunction(
                'price',
                'price(product[, channelCode]): int',
                'The lowest enabled-variant price in the channel, in minor units (0 when the product is not priced).',
                function (array $variables, mixed $product, mixed $channelCode = null): int {
                    $product = $this->assertProduct($product, 'price');

                    return Pricing::lowestPriceInChannel($product, $this->resolveChannelCode($channelCode)) ?? 0;
                },
            ),
        ];
    }

    private function resolveChannelCode(mixed $channelCode): string
    {
        return $this->toNullableString($channelCode) ?? $this->calculationContext->get()->getChannelCode();
    }

    private static function isInChannel(ProductInterface $product, string $channelCode): bool
    {
        foreach ($product->getChannels() as $channel) {
            if ($channel->getCode() === $channelCode) {
                return true;
            }
        }

        return false;
    }
}
