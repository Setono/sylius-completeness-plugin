<?php

declare(strict_types=1);

namespace Setono\SyliusCompletenessPlugin\Form\Type\CheckerConfiguration;

use Sylius\Component\Product\Model\ProductAttributeInterface;
use Sylius\Component\Resource\Repository\RepositoryInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\NotBlank;

/**
 * @extends AbstractType<mixed>
 */
final class HasAttributeConfigurationType extends AbstractType
{
    /**
     * @param RepositoryInterface<ProductAttributeInterface> $attributeRepository
     */
    public function __construct(private readonly RepositoryInterface $attributeRepository)
    {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('attributeCode', ChoiceType::class, [
            'label' => 'setono_sylius_completeness.form.checker_configuration.attribute',
            'choices' => $this->getChoices(),
            'constraints' => [
                new NotBlank(),
            ],
        ]);
    }

    public function getBlockPrefix(): string
    {
        return 'setono_sylius_completeness_checker_configuration_has_attribute';
    }

    /**
     * @return array<string, string>
     */
    private function getChoices(): array
    {
        $choices = [];
        foreach ($this->attributeRepository->findAll() as $attribute) {
            if (!$attribute instanceof ProductAttributeInterface) {
                continue;
            }

            $code = $attribute->getCode();
            if (null === $code) {
                continue;
            }

            $choices[sprintf('%s (%s)', $attribute->getName() ?? $code, $code)] = $code;
        }

        return $choices;
    }
}
