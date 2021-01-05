<?php

declare(strict_types=1);

use PhpCsFixer\Fixer\StringNotation\ExplicitStringVariableFixer;
use SlevomatCodingStandard\Sniffs\Namespaces\ReferenceUsedNamesOnlySniff;
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

    // exclude paths with really nasty code
    $parameters->set(Option::EXCLUDE_PATHS, [__DIR__ . '/src/Services/TypeHintOutput/']);
    $parameters->set(Option::SETS, [
        SetList::COMMON,
        SetList::CLEAN_CODE,
        SetList::DEAD_CODE,
        SetList::PSR_12,
        SetList::PHP_70,
        SetList::PHP_71,
        SetList::SYMPLIFY,
    ]);

    $services->set(LineLengthFixer::class)
        ->call('configure', [[
            LineLengthFixer::LINE_LENGTH => 100,
            LineLengthFixer::INLINE_SHORT_LINES => false,
        ]]);

    $services->set(ReferenceUsedNamesOnlySniff::class)
        ->property('allowFullyQualifiedGlobalFunctions', true)
        ->property('allowPartialUses', true);

    $parameters->set(Option::SKIP, [
        ExplicitStringVariableFixer::class => null,
        StandardizeHereNowDocKeywordFixer::class => null,
        MethodChainingNewlineFixer::class => null,
    ]);
};
