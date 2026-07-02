<?php

declare(strict_types=1);

namespace Setono\SyliusCompletenessPlugin\Tests\Calculator;

use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Setono\SyliusCompletenessPlugin\Calculator\CompletenessCalculator;
use Setono\SyliusCompletenessPlugin\Calculator\ContextInitializer;
use Setono\SyliusCompletenessPlugin\Calculator\Result\GroupScore;
use Setono\SyliusCompletenessPlugin\Calculator\RuleApplicabilityChecker;
use Setono\SyliusCompletenessPlugin\Calculator\RuleWeightResolver;
use Setono\SyliusCompletenessPlugin\Checker\CompletenessCheckContext;
use Setono\SyliusCompletenessPlugin\Checker\CompletenessCheckerInterface;
use Setono\SyliusCompletenessPlugin\Checker\ExpressionChecker;
use Setono\SyliusCompletenessPlugin\Checker\HasAttributeChecker;
use Setono\SyliusCompletenessPlugin\Checker\HasDescriptionChecker;
use Setono\SyliusCompletenessPlugin\Checker\HasImageChecker;
use Setono\SyliusCompletenessPlugin\Checker\HasMinimumImagesChecker;
use Setono\SyliusCompletenessPlugin\Checker\HasNameChecker;
use Setono\SyliusCompletenessPlugin\Checker\IsEnabledChecker;
use Setono\SyliusCompletenessPlugin\Context\CalculationContext;
use Setono\SyliusCompletenessPlugin\Expression\ExpressionEvaluator;
use Setono\SyliusCompletenessPlugin\Expression\ExpressionEvaluatorInterface;
use Setono\SyliusCompletenessPlugin\Expression\ExpressionLanguageFactory;
use Setono\SyliusCompletenessPlugin\Expression\FunctionProvider\AttributeFunctionsProvider;
use Setono\SyliusCompletenessPlugin\Expression\FunctionProvider\ChannelFunctionsProvider;
use Setono\SyliusCompletenessPlugin\Expression\FunctionProvider\CollectionFunctionsProvider;
use Setono\SyliusCompletenessPlugin\Expression\FunctionProvider\ImageFunctionsProvider;
use Setono\SyliusCompletenessPlugin\Expression\FunctionProvider\TaxonFunctionsProvider;
use Setono\SyliusCompletenessPlugin\Expression\FunctionProvider\TextFunctionsProvider;
use Setono\SyliusCompletenessPlugin\Expression\FunctionProvider\TranslationFunctionsProvider;
use Setono\SyliusCompletenessPlugin\Expression\FunctionProvider\VariantFunctionsProvider;
use Setono\SyliusCompletenessPlugin\Model\CompletenessContextSetting;
use Setono\SyliusCompletenessPlugin\Model\CompletenessRule;
use Setono\SyliusCompletenessPlugin\Provider\ContextSettingsProvider;
use Setono\SyliusCompletenessPlugin\Repository\CompletenessContextSettingRepositoryInterface;
use Setono\SyliusCompletenessPlugin\Repository\CompletenessRuleRepositoryInterface;
use Setono\SyliusCompletenessPlugin\Rollup\Rollup;
use Setono\SyliusCompletenessPlugin\Rollup\WeightedAverageRollupStrategy;
use Setono\SyliusCompletenessPlugin\Rubric\RubricVersionManagerInterface;
use Sylius\Component\Core\Model\Channel;
use Sylius\Component\Core\Model\Product;
use Sylius\Component\Core\Model\ProductImage;
use Sylius\Component\Core\Model\ProductTaxon;
use Sylius\Component\Core\Model\Taxon;
use Sylius\Component\Locale\Model\Locale;
use Sylius\Component\Registry\ServiceRegistry;
use Symfony\Component\Clock\MockClock;
use Symfony\Component\DependencyInjection\ServiceLocator;

final class CompletenessCalculatorTest extends TestCase
{
    use ProphecyTrait;

    private CalculationContext $calculationContext;

    private ExpressionEvaluatorInterface $expressionEvaluator;

    private ServiceRegistry $checkerRegistry;

    /** @var ObjectProphecy<CompletenessRuleRepositoryInterface> */
    private ObjectProphecy $ruleRepository;

    /** @var ObjectProphecy<CompletenessContextSettingRepositoryInterface> */
    private ObjectProphecy $contextSettingRepository;

    private MockClock $clock;

    protected function setUp(): void
    {
        $this->calculationContext = new CalculationContext();

        $this->expressionEvaluator = new ExpressionEvaluator(ExpressionLanguageFactory::create([
            new TextFunctionsProvider(),
            new TranslationFunctionsProvider($this->calculationContext),
            new AttributeFunctionsProvider($this->calculationContext),
            new ImageFunctionsProvider(),
            new TaxonFunctionsProvider(),
            new VariantFunctionsProvider(),
            new ChannelFunctionsProvider($this->calculationContext),
            new CollectionFunctionsProvider(),
        ]));

        $this->checkerRegistry = new ServiceRegistry(CompletenessCheckerInterface::class, 'completeness checker');
        $checkers = [
            new IsEnabledChecker(),
            new HasNameChecker(),
            new HasDescriptionChecker(),
            new HasImageChecker(),
            new HasMinimumImagesChecker(),
            new HasAttributeChecker(),
            new ExpressionChecker($this->expressionEvaluator),
        ];
        foreach ($checkers as $checker) {
            $this->checkerRegistry->register($checker::getType(), $checker);
        }

        $this->ruleRepository = $this->prophesize(CompletenessRuleRepositoryInterface::class);

        $this->contextSettingRepository = $this->prophesize(CompletenessContextSettingRepositoryInterface::class);
        $this->contextSettingRepository->findAll()->willReturn([]);

        $this->clock = new MockClock('2026-07-02 12:00:00');
    }

    private function createCalculator(): CompletenessCalculator
    {
        $rubricVersionManager = $this->prophesize(RubricVersionManagerInterface::class);
        $rubricVersionManager->getCurrentVersion()->willReturn(7);

        return new CompletenessCalculator(
            $this->ruleRepository->reveal(),
            $this->checkerRegistry,
            new RuleApplicabilityChecker($this->expressionEvaluator),
            new RuleWeightResolver(['low' => 1.0, 'medium' => 3.0, 'high' => 6.0, 'critical' => 10.0]),
            new ContextInitializer($this->calculationContext),
            new Rollup(
                new ServiceLocator(['weighted_average' => static fn (): WeightedAverageRollupStrategy => new WeightedAverageRollupStrategy()]),
                'weighted_average',
            ),
            new ContextSettingsProvider($this->contextSettingRepository->reveal()),
            $rubricVersionManager->reveal(),
            $this->clock,
        );
    }

    /**
     * @param array<string, list<string>> $channels channel code => locale codes
     */
    private function createProduct(array $channels = ['WEB' => ['en']]): Product
    {
        $product = new Product();
        $product->setCurrentLocale('en');
        $product->setFallbackLocale('en');

        foreach ($channels as $channelCode => $localeCodes) {
            $channel = new Channel();
            $channel->setCode($channelCode);

            foreach ($localeCodes as $localeCode) {
                $locale = new Locale();
                $locale->setCode($localeCode);
                $channel->addLocale($locale);
            }

            $product->addChannel($channel);
        }

        return $product;
    }

    /**
     * @param array<string, mixed> $configuration
     * @param list<string> $channelCodes
     * @param list<string> $localeCodes
     * @param list<string> $taxonCodes
     */
    private function createRule(
        string $code,
        string $type,
        string $weightTier = 'medium',
        array $configuration = [],
        ?string $group = null,
        ?string $condition = null,
        ?string $expression = null,
        array $channelCodes = [],
        array $localeCodes = [],
        array $taxonCodes = [],
    ): CompletenessRule {
        if (null !== $expression) {
            $configuration['expression'] = $expression;
        }

        $rule = new CompletenessRule();
        $rule->setCode($code);
        $rule->setLabel(ucfirst(str_replace('_', ' ', $code)));
        $rule->setType($type);
        $rule->setWeightTier($weightTier);
        $rule->setConfiguration($configuration);
        $rule->setGroup($group);
        $rule->setCondition($condition);
        $rule->setChannelCodes($channelCodes);
        $rule->setLocaleCodes($localeCodes);
        $rule->setTaxonCodes($taxonCodes);

        return $rule;
    }

    /**
     * @test
     */
    public function it_weights_rules_by_tier(): void
    {
        $product = $this->createProduct();
        $product->setName('Shirt');

        $this->ruleRepository->findEnabled()->willReturn([
            $this->createRule('has_name', 'has_name', 'critical'),
            $this->createRule('has_image', 'has_image', 'low'),
        ]);

        $result = $this->createCalculator()->calculate($product);

        $context = $result->contextResults[0];
        self::assertSame(10.0, $context->weightedPassed);
        self::assertSame(11.0, $context->weightedTotal);
        self::assertSame(91, $context->ratio);
        self::assertSame(91, $result->globalRatio);
    }

    /**
     * @test
     */
    public function it_grants_partial_credit(): void
    {
        $product = $this->createProduct();
        $product->addImage(new ProductImage());

        $this->ruleRepository->findEnabled()->willReturn([
            $this->createRule('min_images', 'has_minimum_images', 'medium', ['count' => 4]),
        ]);

        $result = $this->createCalculator()->calculate($product);

        $context = $result->contextResults[0];
        self::assertSame(0.25, $context->ruleResults[0]->score);
        self::assertSame(0.75, $context->weightedPassed);
        self::assertSame(3.0, $context->weightedTotal);
        self::assertSame(25, $context->ratio);
    }

    /**
     * @test
     */
    public function it_computes_per_group_sub_scores(): void
    {
        $product = $this->createProduct();
        $product->setName('Shirt');

        $this->ruleRepository->findEnabled()->willReturn([
            $this->createRule('has_name', 'has_name', 'critical', group: 'Content'),
            $this->createRule('has_image', 'has_image', 'low', group: 'Media'),
            $this->createRule('has_description', 'has_description', 'medium'),
        ]);

        $result = $this->createCalculator()->calculate($product);

        $context = $result->contextResults[0];
        self::assertSame(71, $context->ratio); // 10 / 14

        $groupRatios = [];
        foreach ($context->groupScores as $groupScore) {
            self::assertInstanceOf(GroupScore::class, $groupScore);
            $groupRatios[$groupScore->group ?? '(ungrouped)'] = $groupScore->ratio;
        }

        self::assertSame(['Content' => 100, 'Media' => 0, '(ungrouped)' => 0], $groupRatios);
    }

    /**
     * @test
     */
    public function it_treats_a_throwing_checker_as_errored_with_weight_in_the_denominator(): void
    {
        $product = $this->createProduct();
        $product->setName('Shirt');

        $this->ruleRepository->findEnabled()->willReturn([
            $this->createRule('has_name', 'has_name', 'critical'),
            // has_attribute without configuration throws => errored
            $this->createRule('broken', 'has_attribute', 'medium'),
        ]);

        $result = $this->createCalculator()->calculate($product);

        $context = $result->contextResults[0];
        self::assertSame(77, $context->ratio); // 10 / 13 - the errored rule stays in the denominator

        $errored = $context->ruleResults[1];
        self::assertTrue($errored->errored);
        self::assertSame(0.0, $errored->score);
        self::assertSame(3.0, $errored->weight);
        self::assertNotNull($errored->error);
    }

    /**
     * @test
     */
    public function it_treats_an_unknown_checker_type_as_errored(): void
    {
        $product = $this->createProduct();

        $this->ruleRepository->findEnabled()->willReturn([
            $this->createRule('unknown', 'nonexistent_type'),
        ]);

        $result = $this->createCalculator()->calculate($product);

        $ruleResult = $result->contextResults[0]->ruleResults[0];
        self::assertTrue($ruleResult->errored);
        self::assertStringContainsString('nonexistent_type', (string) $ruleResult->error);
    }

    /**
     * @test
     */
    public function it_treats_a_throwing_condition_as_applying_and_errored(): void
    {
        $product = $this->createProduct();
        $product->setName('Shirt');

        $this->ruleRepository->findEnabled()->willReturn([
            $this->createRule('gated', 'has_name', 'medium', condition: 'unknown_function(product)'),
        ]);

        $result = $this->createCalculator()->calculate($product);

        $context = $result->contextResults[0];
        self::assertSame(0, $context->ratio);

        $ruleResult = $context->ruleResults[0];
        self::assertTrue($ruleResult->errored);
        self::assertSame(3.0, $ruleResult->weight);
    }

    /**
     * @test
     */
    public function it_excludes_condition_gated_rules_from_both_numerator_and_denominator(): void
    {
        $product = $this->createProduct(['WEB' => ['en', 'da']]);
        $product->setName('Shirt'); // en name only

        $this->ruleRepository->findEnabled()->willReturn([
            $this->createRule('only_da', 'has_name', 'critical', condition: 'localeCode == "da"'),
        ]);

        $result = $this->createCalculator()->calculate($product);

        $en = $result->getContextResult('WEB', 'en');
        $da = $result->getContextResult('WEB', 'da');

        self::assertNotNull($en);
        self::assertNull($en->ratio); // rule gated out => context is N/A
        self::assertCount(0, $en->ruleResults);

        self::assertNotNull($da);
        self::assertSame(0, $da->ratio); // applies in da, but the da name is missing
    }

    /**
     * @test
     */
    public function it_skips_rules_scoped_to_another_locale_or_channel(): void
    {
        $product = $this->createProduct(['WEB' => ['en']]);
        $product->setName('Shirt');

        $this->ruleRepository->findEnabled()->willReturn([
            $this->createRule('da_only', 'has_name', 'critical', localeCodes: ['da']),
            $this->createRule('pos_only', 'has_name', 'critical', channelCodes: ['POS']),
            $this->createRule('everywhere', 'has_name', 'low'),
        ]);

        $result = $this->createCalculator()->calculate($product);

        $context = $result->contextResults[0];
        self::assertCount(1, $context->ruleResults);
        self::assertSame('everywhere', $context->ruleResults[0]->code);
        self::assertSame(100, $context->ratio);
    }

    /**
     * @test
     */
    public function it_skips_rules_scoped_to_a_taxon_the_product_does_not_have(): void
    {
        $product = $this->createProduct();
        $product->setName('Shirt');

        $taxon = new Taxon();
        $taxon->setCode('shirts');
        $productTaxon = new ProductTaxon();
        $productTaxon->setTaxon($taxon);
        $productTaxon->setProduct($product);
        $product->addProductTaxon($productTaxon);

        $this->ruleRepository->findEnabled()->willReturn([
            $this->createRule('shirts_rule', 'has_name', 'medium', taxonCodes: ['shirts']),
            $this->createRule('beers_rule', 'has_name', 'medium', taxonCodes: ['beers']),
        ]);

        $result = $this->createCalculator()->calculate($product);

        $context = $result->contextResults[0];
        self::assertCount(1, $context->ruleResults);
        self::assertSame('shirts_rule', $context->ruleResults[0]->code);
    }

    /**
     * @test
     */
    public function it_returns_na_for_contexts_without_applicable_rules_and_null_globally(): void
    {
        $product = $this->createProduct(['WEB' => ['en', 'da']]);

        $this->ruleRepository->findEnabled()->willReturn([]);

        $result = $this->createCalculator()->calculate($product);

        self::assertCount(2, $result->contextResults);
        foreach ($result->contextResults as $context) {
            self::assertNull($context->ratio);
        }
        self::assertNull($result->globalRatio);
    }

    /**
     * @test
     */
    public function it_clamps_out_of_range_expression_scores(): void
    {
        $product = $this->createProduct();

        $this->ruleRepository->findEnabled()->willReturn([
            $this->createRule('too_high', 'expression', 'low', expression: '2.5'),
            $this->createRule('too_low', 'expression', 'low', expression: '0 - 1'),
        ]);

        $result = $this->createCalculator()->calculate($product);

        $context = $result->contextResults[0];
        self::assertSame(1.0, $context->ruleResults[0]->score);
        self::assertSame(0.0, $context->ruleResults[1]->score);
        self::assertSame(50, $context->ratio);
    }

    /**
     * @test
     */
    public function it_copies_the_rule_expression_into_the_checker_configuration(): void
    {
        $product = $this->createProduct();
        $product->setDescription('one two three');

        $this->ruleRepository->findEnabled()->willReturn([
            $this->createRule('min_words', 'expression', 'medium', expression: 'word_count(product.getDescription()) >= 2'),
        ]);

        $result = $this->createCalculator()->calculate($product);

        self::assertSame(100, $result->contextResults[0]->ratio);
    }

    /**
     * @test
     */
    public function it_evaluates_translatable_fields_locale_exactly_per_context(): void
    {
        $product = $this->createProduct(['WEB' => ['en', 'da']]);
        $product->setCurrentLocale('da');
        $product->setFallbackLocale('da');
        $product->setName('Trøje'); // only a da name

        $this->ruleRepository->findEnabled()->willReturn([
            $this->createRule('has_name', 'has_name', 'medium'),
        ]);

        $result = $this->createCalculator()->calculate($product);

        self::assertSame(0, $result->getContextResult('WEB', 'en')?->ratio);
        self::assertSame(100, $result->getContextResult('WEB', 'da')?->ratio);
        self::assertSame(50, $result->globalRatio);
    }

    /**
     * @test
     */
    public function it_applies_rollup_weights_and_exclusions_from_context_settings(): void
    {
        $product = $this->createProduct(['WEB' => ['en', 'da']]);
        $product->setCurrentLocale('da');
        $product->setFallbackLocale('da');
        $product->setName('Trøje'); // da: 100, en: 0

        $excludedSetting = new CompletenessContextSetting();
        $excludedSetting->setChannelCode('WEB');
        $excludedSetting->setLocaleCode('en');
        $excludedSetting->setRollupWeight(0.0);

        $weightedSetting = new CompletenessContextSetting();
        $weightedSetting->setChannelCode('WEB');
        $weightedSetting->setLocaleCode('da');
        $weightedSetting->setRollupWeight(2.0);

        $this->contextSettingRepository->findAll()->willReturn([$excludedSetting, $weightedSetting]);

        $this->ruleRepository->findEnabled()->willReturn([
            $this->createRule('has_name', 'has_name', 'medium'),
        ]);

        $result = $this->createCalculator()->calculate($product);

        $en = $result->getContextResult('WEB', 'en');
        self::assertNotNull($en);
        self::assertTrue($en->excluded);
        self::assertSame(0, $en->ratio); // still computed and shown, just not counted

        self::assertSame(100, $result->globalRatio); // en is excluded from the rollup
    }

    /**
     * @test
     */
    public function it_stamps_the_rubric_version_and_calculation_time(): void
    {
        $product = $this->createProduct();
        $this->ruleRepository->findEnabled()->willReturn([]);

        $result = $this->createCalculator()->calculate($product);

        self::assertSame(7, $result->rubricVersion);
        self::assertSame('2026-07-02 12:00:00', $result->calculatedAt->format('Y-m-d H:i:s'));
    }

    /**
     * @test
     */
    public function it_skips_channels_without_a_code(): void
    {
        $product = new Product();
        $product->setCurrentLocale('en');
        $product->setFallbackLocale('en');

        $locale = new Locale();
        $locale->setCode('en');

        $channel = new Channel(); // no code
        $channel->addLocale($locale);
        $product->addChannel($channel);

        $this->ruleRepository->findEnabled()->willReturn([]);

        $result = $this->createCalculator()->calculate($product);

        self::assertCount(0, $result->contextResults);
        self::assertNull($result->globalRatio);
    }

    /**
     * @test
     */
    public function it_evaluates_an_arbitrary_context_the_product_is_not_assigned_to(): void
    {
        // the product is only in WEB/en, with an English name
        $product = $this->createProduct(['WEB' => ['en']]);
        $product->setName('Shirt');

        $this->ruleRepository->findEnabled()->willReturn([
            $this->createRule('has_name', 'has_name', 'medium'),
        ]);

        $channel = new Channel();
        $channel->setCode('POS');
        $locale = new Locale();
        $locale->setCode('da');

        // preview against POS/da, which the product is NOT assigned to
        $result = $this->createCalculator()->calculateContext($product, new CompletenessCheckContext($channel, $locale));

        self::assertSame('POS', $result->channelCode);
        self::assertSame('da', $result->localeCode);
        // the name only exists in en, so the da context sees an empty name and scores 0
        self::assertSame(0, $result->ratio);
        self::assertCount(1, $result->ruleResults);
    }
}
