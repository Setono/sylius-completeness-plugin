<?php

declare(strict_types=1);

namespace Setono\SyliusCompletenessPlugin\Tests\Form\Type\CheckerConfiguration;

use Setono\SyliusCompletenessPlugin\Form\Type\CheckerConfiguration\HasMinimumImagesConfigurationType;
use Symfony\Component\Form\Extension\Validator\ValidatorExtension;
use Symfony\Component\Form\Test\TypeTestCase;
use Symfony\Component\Validator\Validation;

final class HasMinimumImagesConfigurationTypeTest extends TypeTestCase
{
    /**
     * @return list<\Symfony\Component\Form\FormExtensionInterface>
     */
    protected function getExtensions(): array
    {
        return [
            new ValidatorExtension(Validation::createValidator()),
        ];
    }

    /**
     * @test
     */
    public function it_maps_the_count_to_an_integer(): void
    {
        $form = $this->factory->create(HasMinimumImagesConfigurationType::class);

        $form->submit(['count' => '5']);

        self::assertTrue($form->isSynchronized());
        self::assertSame(['count' => 5], $form->getData());
    }

    /**
     * @test
     */
    public function it_rejects_a_count_below_one(): void
    {
        $form = $this->factory->create(HasMinimumImagesConfigurationType::class);

        $form->submit(['count' => '0']);

        self::assertFalse($form->isValid());
    }
}
