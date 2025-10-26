<?php

use App\Tools\PhpCsFixer\AsymmetricPublicOmissionFixer;

$finder = new PhpCsFixer\Finder()
    ->in(__DIR__)
    ->exclude('var');

return new PhpCsFixer\Config()
    ->setParallelConfig(PhpCsFixer\Runner\Parallel\ParallelConfigFactory::detect())
    ->registerCustomFixers([
        new AsymmetricPublicOmissionFixer(),
    ])
    ->setRiskyAllowed(true)
    ->setRules([
        // Standard
        '@Symfony' => true,
        'declare_strict_types' => true,
        'strict_comparison' => true,
        'strict_param' => true,
        // Php Unit
        'php_unit_method_casing' => ['case' => 'snake_case'],
        'php_unit_attributes' => true,
        'php_unit_data_provider_method_order' => true,
        'php_unit_set_up_tear_down_visibility' => true,
        'php_unit_test_case_static_method_calls' => true,
        'php_unit_data_provider_return_type' => true,
        // Custom
        'App/asymmetric_public_omission' => true,
    ])
    ->setFinder($finder)
    ->setCacheFile(__DIR__.'/var/cache/.php-cs-fixer.cache');
