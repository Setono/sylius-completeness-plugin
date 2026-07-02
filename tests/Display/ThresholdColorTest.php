<?php

declare(strict_types=1);

namespace Setono\SyliusCompletenessPlugin\Tests\Display;

use PHPUnit\Framework\TestCase;
use Setono\SyliusCompletenessPlugin\Display\ThresholdColor;

final class ThresholdColorTest extends TestCase
{
    /**
     * @test
     */
    public function it_is_na_for_a_null_ratio(): void
    {
        self::assertSame(ThresholdColor::NA, ThresholdColor::resolve(null, 80, 20));
    }

    /**
     * @test
     */
    public function it_is_green_at_or_above_the_threshold(): void
    {
        self::assertSame(ThresholdColor::GREEN, ThresholdColor::resolve(80, 80, 20));
        self::assertSame(ThresholdColor::GREEN, ThresholdColor::resolve(100, 80, 20));
    }

    /**
     * @test
     */
    public function it_is_amber_within_the_band_below_the_threshold(): void
    {
        self::assertSame(ThresholdColor::AMBER, ThresholdColor::resolve(79, 80, 20));
        self::assertSame(ThresholdColor::AMBER, ThresholdColor::resolve(60, 80, 20));
    }

    /**
     * @test
     */
    public function it_is_red_below_the_amber_band(): void
    {
        self::assertSame(ThresholdColor::RED, ThresholdColor::resolve(59, 80, 20));
        self::assertSame(ThresholdColor::RED, ThresholdColor::resolve(0, 80, 20));
    }

    /**
     * @test
     */
    public function it_has_no_amber_zone_when_the_band_is_zero(): void
    {
        self::assertSame(ThresholdColor::GREEN, ThresholdColor::resolve(80, 80, 0));
        self::assertSame(ThresholdColor::RED, ThresholdColor::resolve(79, 80, 0));
    }
}
