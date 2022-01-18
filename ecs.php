<?php

declare(strict_types=1);

use PhpCsFixer\Fixer\StringNotation\ExplicitStringVariableFixer;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symplify\CodingStandard\Fixer\LineLength\LineLengthFixer;
use Symplify\CodingStandard\Fixer\Naming\StandardizeHereNowDocKeywordFixer;
use Symplify\CodingStandard\Fixer\Spacing\MethodChainingNewlineFixer;
use Symplify\EasyCodingStandard\ValueObject\Option;
use Symplify\EasyCodingStandard\ValueObject\Set\SetList;

return static function (ContainerConfigurator $containerConfigurator): void {
    $services = $containerConfigurator->services();

    $parameters = $containerConfigurator->parameters();
    $parameters->set(Option::PATHS, [__DIR__ . '/src', __DIR__ . '/ecs.php']);

    $parameters->set(Option::SETS, [
        SetList::COMMON,
        SetList::CLEAN_CODE,
        SetList::PSR_12,
        SetList::PHP_CS_FIXER,
        SetList::SYMPLIFY,
    ]);

    $services->set(LineLengthFixer::class)
        ->call('configure', [[
            LineLengthFixer::LINE_LENGTH => 120,
            LineLengthFixer::INLINE_SHORT_LINES => false,
        ]]);

    $parameters->set(Option::SKIP, [
        ExplicitStringVariableFixer::class => null,
        StandardizeHereNowDocKeywordFixer::class => null,
        MethodChainingNewlineFixer::class => null,
    ]);
};
