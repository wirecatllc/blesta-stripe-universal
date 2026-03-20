<?php

$finder = PhpCsFixer\Finder::create()
    ->in(__DIR__)
    ->name('stripe_universal.php')
    ->append([__DIR__ . '/tests/'])
    ->exclude('vendor')
    ->exclude('Stubs');

return (new PhpCsFixer\Config())
    ->setRules([
        '@PSR12' => true,
        'array_syntax' => ['syntax' => 'short'],
        'no_unused_imports' => true,
        'ordered_imports' => true,
        'single_quote' => true,
    ])
    ->setFinder($finder)
    ->setRiskyAllowed(false);
