<?php

declare(strict_types=1);

namespace Setono\SyliusCompletenessPlugin\Tests\Grid\Filter;

use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Setono\SyliusCompletenessPlugin\Grid\Filter\ScopeCodeFilter;
use Sylius\Component\Grid\Data\DataSourceInterface;
use Sylius\Component\Grid\Data\ExpressionBuilderInterface;

final class ScopeCodeFilterTest extends TestCase
{
    use ProphecyTrait;

    /**
     * @test
     */
    public function it_does_not_restrict_when_no_code_is_selected(): void
    {
        $dataSource = $this->prophesize(DataSourceInterface::class);
        $dataSource->restrict(Argument::any())->shouldNotBeCalled();

        (new ScopeCodeFilter())->apply($dataSource->reveal(), 'channelCode', '', ['field' => 'channelCodes']);
        (new ScopeCodeFilter())->apply($dataSource->reveal(), 'channelCode', null, ['field' => 'channelCodes']);
    }

    /**
     * @test
     */
    public function it_matches_membership_in_the_json_scope_column_for_a_single_code(): void
    {
        $expressionBuilder = $this->prophesize(ExpressionBuilderInterface::class);
        $expressionBuilder->like('channelCodes', '%"WEB"%')->willReturn('EXPR')->shouldBeCalledOnce();

        $dataSource = $this->prophesize(DataSourceInterface::class);
        $dataSource->getExpressionBuilder()->willReturn($expressionBuilder->reveal());
        $dataSource->restrict('EXPR')->shouldBeCalledOnce();

        (new ScopeCodeFilter())->apply($dataSource->reveal(), 'channelCode', 'WEB', ['field' => 'channelCodes']);
    }

    /**
     * @test
     */
    public function it_combines_multiple_selected_codes_with_or(): void
    {
        $expressionBuilder = $this->prophesize(ExpressionBuilderInterface::class);
        $expressionBuilder->like('localeCodes', '%"en_US"%')->willReturn('A')->shouldBeCalledOnce();
        $expressionBuilder->like('localeCodes', '%"de_DE"%')->willReturn('B')->shouldBeCalledOnce();
        $expressionBuilder->orX('A', 'B')->willReturn('OR')->shouldBeCalledOnce();

        $dataSource = $this->prophesize(DataSourceInterface::class);
        $dataSource->getExpressionBuilder()->willReturn($expressionBuilder->reveal());
        $dataSource->restrict('OR')->shouldBeCalledOnce();

        (new ScopeCodeFilter())->apply($dataSource->reveal(), 'localeCode', ['en_US', 'de_DE'], ['field' => 'localeCodes']);
    }

    /**
     * @test
     */
    public function it_falls_back_to_the_filter_name_when_no_field_is_configured(): void
    {
        $expressionBuilder = $this->prophesize(ExpressionBuilderInterface::class);
        $expressionBuilder->like('channelCodes', '%"WEB"%')->willReturn('EXPR')->shouldBeCalledOnce();

        $dataSource = $this->prophesize(DataSourceInterface::class);
        $dataSource->getExpressionBuilder()->willReturn($expressionBuilder->reveal());
        $dataSource->restrict('EXPR')->shouldBeCalledOnce();

        (new ScopeCodeFilter())->apply($dataSource->reveal(), 'channelCodes', 'WEB', []);
    }
}
