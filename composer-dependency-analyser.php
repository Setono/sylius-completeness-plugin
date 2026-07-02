<?php

use ShipMonk\ComposerDependencyAnalyser\Config\Configuration;
use ShipMonk\ComposerDependencyAnalyser\Config\ErrorType;

return (new Configuration())
    ->addPathToExclude(__DIR__ . '/tests')
    // Symfony\Component\Clock\Clock is only referenced from src/Resources/config/services/calculator.xml,
    // which the analyser does not scan, but it is a real runtime dependency.
    ->ignoreErrorsOnPackage('symfony/clock', [ErrorType::UNUSED_DEPENDENCY])
;

// Note: run locally, the analyser reports the Sylius split packages (sylius/core, sylius/channel, ...)
// as "unused" and sylius/sylius as a "dev dependency in production". Both are artifacts of the
// sylius/sylius monorepo metapackage shadowing the split packages it contains. The CI job unsets
// require-dev (removing sylius/sylius) before running the analyser, so there the classes resolve to
// the split packages and the report is clean. These must NOT be ignored here: with require-dev
// unset the errors do not occur, and the analyser fails on unmatched ignores.
