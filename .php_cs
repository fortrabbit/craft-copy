<?php

$finder = PhpCsFixer\Finder::create()
    ->exclude('vendor/')
    ->in(__DIR__);

$rules = [
    '@PSR1' => true,
    '@PSR2' => true,
    '@PHP56Migration' => true,
    '@PHP70Migration' => true,
    'align_multiline_comment' => true,
    'array_indentation' => true,
    'array_syntax' => ['syntax' => 'short'],
    'backtick_to_shell_exec' => true,
    'combine_consecutive_issets' => true,
    'combine_consecutive_unsets' => true,
    'concat_space' => ['spacing' => 'one'],
    'declare_equal_normalize' => ['space' => 'single'],
    'fully_qualified_strict_types' => false,
    'function_declaration' => ['closure_function_spacing' => 'one'],
    'increment_style' => ['style' => 'post'],
    'linebreak_after_opening_tag' => true,
    'method_chaining_indentation' => true,
    'multiline_whitespace_before_semicolons' => true,
    'no_alternative_syntax' => true,
    'no_multiline_whitespace_around_double_arrow' => false,
    'no_short_echo_tag' => true,
    'no_useless_return' => true,
    'single_quote' => false,
    'trailing_comma_in_multiline_array' => false,
    'yoda_style' => ['always_move_variable' => false, 'equal' => false, 'identical' => false, 'less_and_greater' => false]
];

return PhpCsFixer\Config::create()
    ->setRules($rules)
    ->setFinder($finder);

/*
This document has been generated with
https://mlocati.github.io/php-cs-fixer-configurator/
you can change this configuration by importing this YAML code:

fixerSets:
  - '@PSR1'
  - '@PSR2'
  - '@Symfony'
  - '@PHP56Migration'
  - '@PHP70Migration'
fixers:
  align_multiline_comment: true
  array_indentation: true
  array_syntax:
    syntax: short
  backtick_to_shell_exec: true
  braces:
    allow_single_line_closure: true
    position_after_functions_and_oop_constructs: same
  combine_consecutive_issets: true
  combine_consecutive_unsets: true
  concat_space:
    spacing: one
  declare_equal_normalize:
    space: single
  fully_qualified_strict_types: false
  function_declaration:
    closure_function_spacing: one
  increment_style:
    style: post
  linebreak_after_opening_tag: true
  method_chaining_indentation: true
  multiline_whitespace_before_semicolons: true
  no_alternative_syntax: true
  no_multiline_whitespace_around_double_arrow: false
  no_short_echo_tag: true
  no_useless_return: true
  single_quote: false
  trailing_comma_in_multiline_array: false
  yoda_style:
    always_move_variable: false
    equal: false
    identical: false
    less_and_greater: false

*/
