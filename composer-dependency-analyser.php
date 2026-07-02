<?php

use ShipMonk\ComposerDependencyAnalyser\Config\Configuration;
use ShipMonk\ComposerDependencyAnalyser\Config\ErrorType;

return (new Configuration())
    ->addPathToExclude(__DIR__ . '/tests')
    // ServiceRegistry is only referenced from src/Resources/config/services/checker.xml,
    // which the analyser does not scan, but it is a real runtime dependency
    ->ignoreErrorsOnPackage('sylius/registry', [ErrorType::UNUSED_DEPENDENCY])
;
