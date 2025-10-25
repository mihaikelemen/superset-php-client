<?php

$finder = PhpCsFixer\Finder::create()
    ->in([
        __DIR__ . '/src',
        __DIR__ . '/tests',
    ])
    ->name('*.php')
    ->ignoreDotFiles(true)
    ->ignoreVCS(true);

return (new PhpCsFixer\Config())
    ->setFinder($finder)
    ->setRules([
        '@Symfony' => true,
        'array_syntax' => ['syntax' => 'short'],
        'concat_space' => ['spacing' => 'one'],
        'array_indentation' => true,
        'ternary_operator_spaces' => true,
        'whitespace_after_comma_in_array' => true,
    ])
    ->setParallelConfig(PhpCsFixer\Runner\Parallel\ParallelConfigFactory::detect());
