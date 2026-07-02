<?php

declare(strict_types=1);

namespace Setono\SyliusCompletenessPlugin\Tests\Functional;

use Setono\SyliusCompletenessPlugin\Expression\ExpressionFunctionDocumentationProviderInterface;
use Setono\SyliusCompletenessPlugin\Expression\ExpressionFunctionNameProviderInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * Guards that the editor autocompletion documentation stays in sync with the actual set of
 * registered expression functions, so a newly added built-in function cannot ship undocumented.
 *
 * @group functional
 */
final class ExpressionFunctionDocumentationTest extends KernelTestCase
{
    /**
     * @test
     */
    public function every_registered_built_in_function_is_documented(): void
    {
        self::bootKernel();

        /** @var ExpressionFunctionNameProviderInterface $nameProvider */
        $nameProvider = self::getContainer()->get('setono_sylius_completeness.expression_function_name_provider');
        /** @var ExpressionFunctionDocumentationProviderInterface $documentationProvider */
        $documentationProvider = self::getContainer()->get('setono_sylius_completeness.expression_function_documentation_provider');

        $names = $nameProvider->getNames();
        $documented = array_keys($documentationProvider->getDocumentation());

        self::assertSame(
            [],
            array_values(array_diff($names, $documented)),
            'Every registered expression function must be documented for the editor autocompletion',
        );

        self::assertSame(
            [],
            array_values(array_diff($documented, $names)),
            'The documentation must not reference functions that are not registered',
        );
    }
}
