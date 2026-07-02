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
                fn (array $variables, mixed $product): bool => $this->assertProduct($product, 'is_enabled')->isEnabled(),
            ),
            $this->createFunction(
                'is_in_channel',
                function (array $variables, mixed $product, mixed $channelCode = null): bool {
                    $product = $this->assertProduct($product, 'is_in_channel');

                    return self::isInChannel($product, $this->resolveChannelCode($channelCode));
                },
            ),
            $this->createFunction(
                'channel_count',
                fn (array $variables, mixed $product): int => $this->assertProduct($product, 'channel_count')->getChannels()->count(),
            ),
            $this->createFunction(
                'has_price',
                function (array $variables, mixed $product, mixed $channelCode = null): bool {
                    $product = $this->assertProduct($product, 'has_price');

                    return Pricing::hasPriceInChannel($product, $this->resolveChannelCode($channelCode));
                },
            ),
            $this->createFunction(
                'price',
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
