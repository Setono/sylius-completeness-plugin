<?php

declare(strict_types=1);

namespace Setono\SyliusCompletenessPlugin\Tests\Checker;

use PHPUnit\Framework\TestCase;
use Setono\SyliusCompletenessPlugin\Checker\CompletenessCheckContext;
use Sylius\Component\Core\Model\Channel;
use Sylius\Component\Locale\Model\Locale;

final class CompletenessCheckContextTest extends TestCase
{
    /**
     * @test
     */
    public function it_derives_codes_from_the_channel_and_locale(): void
    {
        $channel = new Channel();
        $channel->setCode('WEB');

        $locale = new Locale();
        $locale->setCode('da');

        $context = new CompletenessCheckContext($channel, $locale);

        self::assertSame('WEB', $context->getChannelCode());
        self::assertSame('da', $context->getLocaleCode());
        self::assertSame($channel, $context->getChannel());
        self::assertSame($locale, $context->getLocale());
    }

    /**
     * @test
     */
    public function it_throws_when_the_channel_has_no_code(): void
    {
        $locale = new Locale();
        $locale->setCode('da');

        $this->expectException(\InvalidArgumentException::class);

        new CompletenessCheckContext(new Channel(), $locale);
    }

    /**
     * @test
     */
    public function it_throws_when_the_locale_has_no_code(): void
    {
        $channel = new Channel();
        $channel->setCode('WEB');

        $this->expectException(\InvalidArgumentException::class);

        new CompletenessCheckContext($channel, new Locale());
    }
}
