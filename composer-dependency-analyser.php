<?php

use ShipMonk\ComposerDependencyAnalyser\Config\Configuration;
use ShipMonk\ComposerDependencyAnalyser\Config\ErrorType;

return (new Configuration())
    ->addPathToExclude(__DIR__ . '/tests')
    // Symfony\Component\Clock\Clock is only referenced from src/Resources/config/services/calculator.xml,
    // which the analyser does not scan, but it is a real runtime dependency
    ->ignoreErrorsOnPackage('symfony/clock', [ErrorType::UNUSED_DEPENDENCY])
    // Locally, sylius/sylius (a require-dev metapackage of the split components we require in
    // "require") shadows the individual components, so the analyser attributes their classes to it.
    // CI unsets require-dev before running the analyser, so there the classes resolve to the split
    // packages and this never fires - the ignore only keeps local runs clean.
    ->ignoreErrorsOnPackage('sylius/sylius', [ErrorType::DEV_DEPENDENCY_IN_PROD])
;
