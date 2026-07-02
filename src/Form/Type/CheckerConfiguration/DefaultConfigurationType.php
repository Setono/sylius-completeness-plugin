<?php

declare(strict_types=1);

namespace Setono\SyliusCompletenessPlugin\Form\Type\CheckerConfiguration;

use Symfony\Component\Form\AbstractType;

/**
 * The empty configuration form shared by all parameterless checkers
 *
 * @extends AbstractType<mixed>
 */
final class DefaultConfigurationType extends AbstractType
{
    public function getBlockPrefix(): string
    {
        return 'setono_sylius_completeness_checker_configuration_default';
    }
}
