<?php

declare(strict_types=1);

namespace Setono\SyliusCompletenessPlugin\Tests\Form\Type;

use Prophecy\PhpUnit\ProphecyTrait;
use Setono\SyliusCompletenessPlugin\Form\Type\ChannelCodeChoiceType;
use Setono\SyliusCompletenessPlugin\Form\Type\CheckerChoiceType;
use Setono\SyliusCompletenessPlugin\Form\Type\CheckerConfiguration\DefaultConfigurationType;
use Setono\SyliusCompletenessPlugin\Form\Type\CheckerConfiguration\HasMinimumImagesConfigurationType;
use Setono\SyliusCompletenessPlugin\Form\Type\CompletenessRuleType;
use Setono\SyliusCompletenessPlugin\Form\Type\LocaleCodeChoiceType;
use Setono\SyliusCompletenessPlugin\Form\Type\TaxonCodesAutocompleteChoiceType;
use Setono\SyliusCompletenessPlugin\Form\Type\WeightTierChoiceType;
use Setono\SyliusCompletenessPlugin\Model\CompletenessRule;
use Sylius\Bundle\CoreBundle\Form\DataTransformer\TaxonsToCodesTransformer;
use Sylius\Bundle\ResourceBundle\Form\Registry\FormTypeRegistry;
use Sylius\Bundle\ResourceBundle\Form\Type\ResourceAutocompleteChoiceType;
use Sylius\Bundle\TaxonomyBundle\Form\Type\TaxonAutocompleteChoiceType;
use Sylius\Component\Registry\ServiceRegistryInterface;
use Sylius\Component\Resource\Repository\RepositoryInterface;
use Sylius\Component\Taxonomy\Repository\TaxonRepositoryInterface;
use Symfony\Component\Form\Extension\Validator\ValidatorExtension;
use Symfony\Component\Form\PreloadedExtension;
use Symfony\Component\Form\Test\TypeTestCase;
use Symfony\Component\Validator\Validation;

final class CompletenessRuleTypeTest extends TypeTestCase
{
    use ProphecyTrait;

    /**
     * @return list<\Symfony\Component\Form\FormExtensionInterface>
     */
    protected function getExtensions(): array
    {
        $formTypeRegistry = new FormTypeRegistry();
        $formTypeRegistry->add('has_name', 'default', DefaultConfigurationType::class);
        $formTypeRegistry->add('has_minimum_images', 'default', HasMinimumImagesConfigurationType::class);
        $formTypeRegistry->add('expression', 'default', DefaultConfigurationType::class);

        $emptyRepository = $this->prophesize(RepositoryInterface::class);
        $emptyRepository->findAll()->willReturn([]);

        // the taxon field is a Sylius resource autocomplete: register its whole parent chain
        // (TaxonCodesAutocomplete -> TaxonAutocomplete -> ResourceAutocomplete -> HiddenType) so
        // the bare TypeTestCase can build it. The registry only needs to yield a repository
        $taxonRepository = $this->prophesize(TaxonRepositoryInterface::class);
        $resourceRepositoryRegistry = $this->prophesize(ServiceRegistryInterface::class);
        $resourceRepositoryRegistry->get('sylius.taxon')->willReturn($emptyRepository->reveal());

        return [
            new PreloadedExtension([
                new CompletenessRuleType(CompletenessRule::class, [], $formTypeRegistry, true),
                new CheckerChoiceType([
                    'has_name' => 'Has name',
                    'has_minimum_images' => 'Has minimum images',
                    'expression' => 'Expression',
                ]),
                new WeightTierChoiceType(['low' => 1.0, 'medium' => 3.0, 'high' => 6.0, 'critical' => 10.0]),
                new ChannelCodeChoiceType($emptyRepository->reveal()),
                new LocaleCodeChoiceType($emptyRepository->reveal()),
                new TaxonCodesAutocompleteChoiceType(new TaxonsToCodesTransformer($taxonRepository->reveal())),
                new TaxonAutocompleteChoiceType(),
                new ResourceAutocompleteChoiceType($resourceRepositoryRegistry->reveal()),
            ], []),
            new ValidatorExtension(Validation::createValidator()),
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
            'label' => 'Has a name',
            'code' => '',
            'group' => '',
            'type' => 'has_name',
            'weightTier' => 'medium',
            'condition' => '',
            'expression' => '',
            'channelCodes' => [],
            'localeCodes' => [],
            'taxonCodes' => '',
            'position' => '0',
            'enabled' => '1',
        ], $overrides);
    }

    /**
     * @test
     */
    public function it_adds_the_configuration_sub_form_for_the_initial_type(): void
    {
        $rule = new CompletenessRule();
        $rule->setType('has_minimum_images');
        $rule->setConfiguration(['count' => 3]);

        $form = $this->factory->create(CompletenessRuleType::class, $rule);

        self::assertTrue($form->has('configuration'));
        self::assertTrue($form->get('configuration')->has('count'));
    }

    /**
     * @test
     */
    public function it_swaps_the_configuration_sub_form_based_on_the_submitted_type(): void
    {
        $form = $this->factory->create(CompletenessRuleType::class);

        $form->submit($this->submitData([
            'type' => 'has_minimum_images',
            'configuration' => ['count' => '5'],
        ]));

        self::assertTrue($form->isSynchronized());

        /** @var CompletenessRule $rule */
        $rule = $form->getData();
        self::assertSame('has_minimum_images', $rule->getType());
        self::assertSame(['count' => 5], $rule->getConfiguration());
    }

    /**
     * @test
     */
    public function it_generates_the_code_from_the_label_when_blank(): void
    {
        $form = $this->factory->create(CompletenessRuleType::class);

        $form->submit($this->submitData([
            'label' => 'Has a Danish Name!',
            'code' => '',
        ]));

        /** @var CompletenessRule $rule */
        $rule = $form->getData();
        self::assertSame('has_a_danish_name', $rule->getCode());
    }

    /**
     * @test
     */
    public function it_keeps_a_submitted_code(): void
    {
        $form = $this->factory->create(CompletenessRuleType::class);

        $form->submit($this->submitData([
            'code' => 'my_custom_code',
        ]));

        /** @var CompletenessRule $rule */
        $rule = $form->getData();
        self::assertSame('my_custom_code', $rule->getCode());
    }

    /**
     * @test
     */
    public function it_nulls_the_expression_for_curated_checker_types(): void
    {
        $form = $this->factory->create(CompletenessRuleType::class);

        $form->submit($this->submitData([
            'type' => 'has_name',
            'expression' => 'word_count(product.getDescription()) >= 200',
        ]));

        /** @var CompletenessRule $rule */
        $rule = $form->getData();
        self::assertNull($rule->getExpression());
    }

    /**
     * @test
     */
    public function it_keeps_the_expression_for_the_expression_type(): void
    {
        $form = $this->factory->create(CompletenessRuleType::class);

        $form->submit($this->submitData([
            'type' => 'expression',
            'expression' => 'word_count(product.getDescription()) >= 200',
        ]));

        /** @var CompletenessRule $rule */
        $rule = $form->getData();
        self::assertSame('word_count(product.getDescription()) >= 200', $rule->getExpression());
    }

    /**
     * @test
     */
    public function it_maps_scope_and_condition_fields(): void
    {
        $form = $this->factory->create(CompletenessRuleType::class);

        $form->submit($this->submitData([
            'condition' => 'localeCode == "da"',
        ]));

        /** @var CompletenessRule $rule */
        $rule = $form->getData();
        self::assertSame('localeCode == "da"', $rule->getCondition());
        self::assertSame([], $rule->getChannelCodes());
        self::assertSame([], $rule->getTaxonCodes());
        self::assertTrue($rule->isEnabled());
        self::assertSame(0, $rule->getPosition());
    }
}
