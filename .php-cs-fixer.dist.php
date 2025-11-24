<?php

declare(strict_types=1);

$finder = (new PhpCsFixer\Finder())
    ->in([
        __DIR__.'/src/BCDonations',
        __DIR__.'/src/BCPayments',
        __DIR__.'/src/SharedKernel',
        __DIR__.'/src/SharedApplication',
        __DIR__.'/src/SharedInfrastructure',
        __DIR__.'/tests',
    ])
;

return (new PhpCsFixer\Config())
    ->setRules([
        '@PSR12' => true,
        'declare_strict_types' => true,
        'no_unused_imports' => true,
        'ordered_imports' => true,
    ])
    ->setRiskyAllowed(true)
    ->setFinder($finder)
;
