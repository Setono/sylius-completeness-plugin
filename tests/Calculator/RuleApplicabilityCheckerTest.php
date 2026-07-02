<?php

declare(strict_types=1);

namespace Setono\SyliusCompletenessPlugin\Tests\Calculator;

use PHPUnit\Framework\TestCase;
use Setono\SyliusCompletenessPlugin\Calculator\RuleApplicabilityChecker;
use Setono\SyliusCompletenessPlugin\Checker\CompletenessCheckContext;
use Setono\SyliusCompletenessPlugin\Expression\ExpressionEvaluator;
use Setono\SyliusCompletenessPlugin\Expression\ExpressionLanguageFactory;
use Setono\SyliusCompletenessPlugin\Model\CompletenessRule;
use Sylius\Component\Core\Model\Channel;
use Sylius\Component\Core\Model\Product;
use Sylius\Component\Core\Model\ProductTaxon;
use Sylius\Component\Core\Model\Taxon;
use Sylius\Component\Locale\Model\Locale;

final class RuleApplicabilityCheckerTest extends TestCase
{
    private RuleApplicabilityChecker $checker;

    protected function setUp(): void
    {
        $this->checker = new RuleApplicabilityChecker(
            new ExpressionEvaluator(ExpressionLanguageFactory::create([])),
        );
    }

    private function createContext(string $channelCode = 'WEB', string $localeCode = 'en'): CompletenessCheckContext
    {
        $channel = new Channel();
        $channel->setCode($channelCode);

        $locale = new Locale();
        $locale->setCode($localeCode);

        return new CompletenessCheckContext($channel, $locale);
    }

    private function createRule(): CompletenessRule
    {
        $rule = new CompletenessRule();
        $rule->setType('has_name');

        return $rule;
    }

    /**
     * @test
     */
    public function it_skips_disabled_rules(): void
    {
        $rule = $this->createRule();
        $rule->setEnabled(false);

        $applicability = $this->checker->check($rule, new Product(), $this->createContext());

        self::assertFalse($applicability->applies);
        self::assertFalse($applicability->errored);
    }

    /**
     * @test
     */
    public function it_matches_channel_and_locale_scopes(): void
    {
        $rule = $this->createRule();
        $rule->setChannelCodes(['WEB', 'MOBILE']);
        $rule->setLocaleCodes(['en']);

        self::assertTrue($this->checker->check($rule, new Product(), $this->createContext('WEB', 'en'))->applies);
        self::assertTrue($this->checker->check($rule, new Product(), $this->createContext('MOBILE', 'en'))->applies);
        self::assertFalse($this->checker->check($rule, new Product(), $this->createContext('POS', 'en'))->applies);
        self::assertFalse($this->checker->check($rule, new Product(), $this->createContext('WEB', 'da'))->applies);
    }

    /**
     * @test
     */
    public function it_matches_the_taxon_scope_against_main_and_product_taxons(): void
    {
        $rule = $this->createRule();
        $rule->setTaxonCodes(['shirts']);

        $productWithout = new Product();
        self::assertFalse($this->checker->check($rule, $productWithout, $this->createContext())->applies);

        $mainTaxon = new Taxon();
        $mainTaxon->setCode('shirts');
        $productWithMain = new Product();
        $productWithMain->setMainTaxon($mainTaxon);
        self::assertTrue($this->checker->check($rule, $productWithMain, $this->createContext())->applies);

        $taxon = new Taxon();
        $taxon->setCode('shirts');
        $productTaxon = new ProductTaxon();
        $productTaxon->setTaxon($taxon);
        $productWithProductTaxon = new Product();
        $productTaxon->setProduct($productWithProductTaxon);
        $productWithProductTaxon->addProductTaxon($productTaxon);
        self::assertTrue($this->checker->check($rule, $productWithProductTaxon, $this->createContext())->applies);
    }

    /**
     * @test
     */
    public function it_applies_when_no_condition_is_set(): void
    {
        self::assertTrue($this->checker->check($this->createRule(), new Product(), $this->createContext())->applies);
    }

    /**
     * @test
     */
    public function it_gates_by_condition(): void
    {
        $rule = $this->createRule();

        $rule->setCondition('localeCode == "en"');
        self::assertTrue($this->checker->check($rule, new Product(), $this->createContext('WEB', 'en'))->applies);
        self::assertFalse($this->checker->check($rule, new Product(), $this->createContext('WEB', 'da'))->applies);
    }

    /**
     * @test
     */
    public function it_treats_a_throwing_condition_as_applying_and_errored(): void
    {
        $rule = $this->createRule();
        $rule->setCondition('unknown_function(product)');

        $applicability = $this->checker->check($rule, new Product(), $this->createContext());

        self::assertTrue($applicability->applies);
        self::assertTrue($applicability->errored);
        self::assertNotNull($applicability->error);
    }

    /**
     * @test
     */
    public function it_treats_a_non_boolean_condition_result_as_errored(): void
    {
        $rule = $this->createRule();
        $rule->setCondition('"a string"');

        $applicability = $this->checker->check($rule, new Product(), $this->createContext());

        self::assertTrue($applicability->applies);
        self::assertTrue($applicability->errored);
        self::assertStringContainsString('boolean', (string) $applicability->error);
    }
}
