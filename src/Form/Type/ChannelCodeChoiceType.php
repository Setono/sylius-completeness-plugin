<?php

declare(strict_types=1);

namespace Setono\SyliusCompletenessPlugin\Form\Type;

use Sylius\Component\Core\Model\ChannelInterface;
use Sylius\Component\Resource\Repository\RepositoryInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * A choice of channel CODES (the completeness entities store codes, not relations)
 *
 * @extends AbstractType<mixed>
 */
final class ChannelCodeChoiceType extends AbstractType
{
    /**
     * @param RepositoryInterface<ChannelInterface> $channelRepository
     */
    public function __construct(private readonly RepositoryInterface $channelRepository)
    {
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'choices' => $this->getChoices(),
        ]);
    }

    public function getParent(): string
    {
        return ChoiceType::class;
    }

    public function getBlockPrefix(): string
    {
        return 'setono_sylius_completeness_channel_code_choice';
    }

    /**
     * @return array<string, string>
     */
    private function getChoices(): array
    {
        $choices = [];
        foreach ($this->channelRepository->findAll() as $channel) {
            if (!$channel instanceof ChannelInterface) {
                continue;
            }

            $code = $channel->getCode();
            if (null === $code) {
                continue;
            }

            $choices[sprintf('%s (%s)', $channel->getName() ?? $code, $code)] = $code;
        }

        return $choices;
    }
}
