<?php

use ShipMonk\ComposerDependencyAnalyser\Config\Configuration;
use ShipMonk\ComposerDependencyAnalyser\Config\ErrorType;

return (new Configuration())
    ->addPathToExclude(__DIR__ . '/tests')
    // Symfony\Component\Clock\Clock is only referenced from src/Resources/config/services/calculator.xml,
    // which the analyser does not scan, but it is a real runtime dependency
    ->ignoreErrorsOnPackage('symfony/clock', [ErrorType::UNUSED_DEPENDENCY])
;
