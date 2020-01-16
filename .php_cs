<?php

$finder = PhpCsFixer\Finder::create()
    ->exclude('vendor/')
    ->in(__DIR__);

$rules = [
    '@PSR12' => true
];

return PhpCsFixer\Config::create()
    ->setRules($rules)
    ->setFinder($finder);

