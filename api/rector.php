<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;
use Rector\Php55\Rector\String_\StringClassNameToClassConstantRector;
use Rector\Set\ValueObject\LevelSetList;
use Rector\Set\ValueObject\SetList;
use Rector\Symfony\Set\SymfonySetList;
use Rector\TypeDeclaration\Rector\ClassMethod\ReturnNeverTypeRector;
use Rector\Php81\Rector\Property\ReadOnlyPropertyRector;

return static function (RectorConfig $rectorConfig): void {
    $rectorConfig->importNames();
    $rectorConfig->paths([__DIR__ . '/src', __DIR__ . '/tests']);
    //$rectorConfig->paths([__DIR__ . '/src/Security/Voter/DocumentVoter.php']);

    $rectorConfig->parallel();

    $rectorConfig->skip([
        '*/Fixture/*',
        '*/Source/*',
        '*/Source*/*',
        '*/tests/*/Fixture*/Expected/*',
        StringClassNameToClassConstantRector::class => [__DIR__ . '/config'],

        \Rector\Naming\Rector\Foreach_\RenameForeachValueVariableToMatchMethodCallReturnTypeRector::class => [
            // "data" => "datum" false positive
            __DIR__ . '/src/Rector/ClassMethod/AddRouteAnnotationRector.php',
        ],

        // marked as skipped
        ReturnNeverTypeRector::class => [
            '*/tests/*'
        ],
        
        // ########## Below added by Michael 2022-7-17 ################

        //https://github.com/rectorphp/rector/blob/main/docs/how_to_ignore_rule_or_paths.md
        
        // PHPed is ugly
        ReadOnlyPropertyRector::class,

        //  Note that never had a use class so these must have been executed!!!
        StringClassNameToClassConstantRector::class,
        // some classes in config might not exist without dev dependencies
        SplitStringClassConstantToClassConstFetchRector::class,

        //ChangeReadOnlyPropertyWithDefaultValueToConstantRector::class,

        // Causes issues with Doctrine.
        FinalizeClassesWithoutChildrenRector::class => [__DIR__ . '/src/Entity',],
        PrivatizeFinalClassPropertyRector::class => [__DIR__ . '/src/Entity',],
    ]);

    $rectorConfig->ruleWithConfiguration(StringClassNameToClassConstantRector::class, [
        'Symfony\*',
        'Twig_*',
        'Twig*',
        'Swift_*',
        'Doctrine\*',
        // loaded from project itself
        'Psr\Container\ContainerInterface',
        'Symfony\Component\Routing\RouterInterface',
        'Symfony\Component\DependencyInjection\Container',
    ]);

    // for testing
    //$rectorConfig->import(__DIR__ . '/config/config.php');

    $rectorConfig->sets([
        LevelSetList::UP_TO_PHP_81,
        SetList::CODE_QUALITY,
        SetList::DEAD_CODE,
        SetList::NAMING,
        SymfonySetList::SYMFONY_60,
    ]);
};