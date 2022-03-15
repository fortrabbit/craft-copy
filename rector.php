<?php

declare(strict_types=1);

use craft\rector\SetList as CraftSetList;
use Rector\CodeQuality\Rector\If_\SimplifyIfReturnBoolRector;
use Rector\CodeQuality\Rector\Ternary\UnnecessaryTernaryExpressionRector;
use Rector\CodeQuality\Rector\If_\ExplicitBoolCompareRector;
use Rector\CodingStyle\Rector\ClassConst\RemoveFinalFromConstRector;
use Rector\CodingStyle\Rector\Encapsed\EncapsedStringsToSprintfRector;
use Rector\Core\Configuration\Option;
use Rector\Core\ValueObject\PhpVersion;
use Rector\Set\ValueObject\LevelSetList;
use Rector\Set\ValueObject\SetList;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

return static function (ContainerConfigurator $containerConfigurator): void {
    // Optional directories to skip
    $parameters = $containerConfigurator->parameters();
    $parameters->set(Option::PHP_VERSION_FEATURES, PhpVersion::PHP_80);
    $parameters->set(Option::SKIP, [
            EncapsedStringsToSprintfRector::class,
            RemoveFinalFromConstRector::class,
            SimplifyIfReturnBoolRector::class,
            UnnecessaryTernaryExpressionRector::class,
            ExplicitBoolCompareRector::class
    ]);


    // Craft Version
    $containerConfigurator->import(CraftSetList::CRAFT_CMS_40);
    // PHP Version
    $containerConfigurator->import(LevelSetList::UP_TO_PHP_80);
    // Other
    $containerConfigurator->import(SetList::CODE_QUALITY);
    $containerConfigurator->import(SetList::CODING_STYLE);
    $containerConfigurator->import(SetList::DEAD_CODE);
    $containerConfigurator->import(SetList::TYPE_DECLARATION);
};
