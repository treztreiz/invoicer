<?php

$finder = new PhpCsFixer\Finder()
    ->in(__DIR__)
    ->exclude('var');

return new PhpCsFixer\Config()
    ->setParallelConfig(PhpCsFixer\Runner\Parallel\ParallelConfigFactory::detect())
    ->setRules([
        '@Symfony' => true,
        'php_unit_method_casing' => ['case' => 'snake_case'],
    ])
    ->setFinder($finder)
    ->setCacheFile(__DIR__.'/cache/.php-cs-fixer.cache');
