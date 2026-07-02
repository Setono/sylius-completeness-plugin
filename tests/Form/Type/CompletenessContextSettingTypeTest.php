<?php

declare(strict_types=1);

namespace Setono\SyliusCompletenessPlugin\Tests\Form\Type;

use Prophecy\PhpUnit\ProphecyTrait;
use Setono\SyliusCompletenessPlugin\Form\Type\ChannelCodeChoiceType;
use Setono\SyliusCompletenessPlugin\Form\Type\CompletenessContextSettingType;
use Setono\SyliusCompletenessPlugin\Form\Type\LocaleCodeChoiceType;
use Setono\SyliusCompletenessPlugin\Model\CompletenessContextSetting;
use Sylius\Component\Core\Model\Channel;
use Sylius\Component\Locale\Model\Locale;
use Sylius\Component\Resource\Repository\RepositoryInterface;
use Symfony\Component\Form\PreloadedExtension;
use Symfony\Component\Form\Test\TypeTestCase;

final class CompletenessContextSettingTypeTest extends TypeTestCase
{
    use ProphecyTrait;

    /**
     * @return list<\Symfony\Component\Form\FormExtensionInterface>
     */
    protected function getExtensions(): array
    {
        $channel = new Channel();
        $channel->setCode('WEB');
        $channel->setName('Web store');

        $channelRepository = $this->prophesize(RepositoryInterface::class);
        $channelRepository->findAll()->willReturn([$channel]);

        $locale = new Locale();
        $locale->setCode('en');

        $localeRepository = $this->prophesize(RepositoryInterface::class);
        $localeRepository->findAll()->willReturn([$locale]);

        return [
            new PreloadedExtension([
                new CompletenessContextSettingType(CompletenessContextSetting::class, [], 80),
                new ChannelCodeChoiceType($channelRepository->reveal()),
                new LocaleCodeChoiceType($localeRepository->reveal()),
            ], []),
        ];
    }

    /**
     * @param array<string, mixed> $overrides
     *
     * @return array<string, mixed>
     */
    private function submitData(array $overrides = []): array
    {
        return array_merge([
            'channelCode' => 'WEB',
            'localeCode' => 'en',
            'threshold' => '',
            'rollupWeight' => '',
        ], $overrides);
    }

    /**
     * @test
     */
    public function it_sets_the_rollup_weight_to_zero_when_unchecked(): void
    {
        $form = $this->factory->create(CompletenessContextSettingType::class);

        $form->submit($this->submitData([
            'rollupWeight' => '2',
            // countsTowardOverall not submitted => unchecked
        ]));

        /** @var CompletenessContextSetting $setting */
        $setting = $form->getData();
        self::assertSame(0.0, $setting->getRollupWeight());
    }

    /**
     * @test
     */
    public function it_defaults_the_rollup_weight_to_one_when_checked_without_a_weight(): void
    {
        $form = $this->factory->create(CompletenessContextSettingType::class);

        $form->submit($this->submitData([
            'countsTowardOverall' => '1',
        ]));

        /** @var CompletenessContextSetting $setting */
        $setting = $form->getData();
        self::assertSame(1.0, $setting->getRollupWeight());
    }

    /**
     * @test
     */
    public function it_keeps_an_explicit_weight_when_checked(): void
    {
        $form = $this->factory->create(CompletenessContextSettingType::class);

        $form->submit($this->submitData([
            'countsTowardOverall' => '1',
            'rollupWeight' => '2.5',
        ]));

        /** @var CompletenessContextSetting $setting */
        $setting = $form->getData();
        self::assertSame(2.5, $setting->getRollupWeight());
    }

    /**
     * @test
     */
    public function it_initializes_the_checkbox_from_the_weight(): void
    {
        $excluded = new CompletenessContextSetting();
        $excluded->setRollupWeight(0.0);

        $form = $this->factory->create(CompletenessContextSettingType::class, $excluded);
        self::assertFalse($form->get('countsTowardOverall')->getData());

        $counted = new CompletenessContextSetting();
        $counted->setRollupWeight(2.0);

        $form = $this->factory->create(CompletenessContextSettingType::class, $counted);
        self::assertTrue($form->get('countsTowardOverall')->getData());
    }

    /**
     * @test
     */
    public function it_maps_the_context_and_threshold(): void
    {
        $form = $this->factory->create(CompletenessContextSettingType::class);

        $form->submit($this->submitData([
            'threshold' => '90',
            'countsTowardOverall' => '1',
        ]));

        /** @var CompletenessContextSetting $setting */
        $setting = $form->getData();
        self::assertSame('WEB', $setting->getChannelCode());
        self::assertSame('en', $setting->getLocaleCode());
        self::assertSame(90, $setting->getThreshold());
    }
}
