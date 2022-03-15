<?php

declare(strict_types=1);

use craft\rector\SetList as CraftSetList;
use Rector\Core\Configuration\Option;
use Rector\Set\ValueObject\LevelSetList;
use Rector\Set\ValueObject\SetList;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

return static function (ContainerConfigurator $containerConfigurator): void {
    // Optional directories to skip
    $parameters = $containerConfigurator->parameters();
    /** @noinspection PhpParamsInspection */
    $parameters->set(Option::SKIP, [
        // __DIR__ . '/vendor/nystudio107/craft-seomatic/src/integrations',
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
