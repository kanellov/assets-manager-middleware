<?php

/**
 * kanellov/assets-manager-middleware
 * 
 * @link https://github.com/kanellov/assets-manager-middleware for the canonical source repository
 * @copyright Copyright (c) 2016 Vassilis Kanellopoulos (http://kanellov.com)
 * @license GNU GPLv3 http://www.gnu.org/licenses/gpl-3.0-standalone.html
 */

$finder = Symfony\CS\Finder\DefaultFinder::create()
    ->in(['src', 'tests']);

$config = Symfony\CS\Config\Config::create();
$config->level(null);
$config->fixers([
    'braces',
    'duplicate_semicolon',
    'elseif',
    'empty_return',
    'encoding',
    'eof_ending',
    'function_call_space',
    'function_declaration',
    'indentation',
    'join_function',
    'line_after_namespace',
    'linefeed',
    'lowercase_constants',
    'lowercase_keywords',
    'parenthesis',
    'multiple_use',
    'method_argument_space',
    'object_operator',
    'php_closing_tag',
    'psr0',
    'single_line_after_imports',
    'remove_lines_between_uses',
    'short_tag',
    'standardize_not_equal',
    'trailing_spaces',
    'unused_use',
    'visibility',
    'whitespacy_lines',
    'ternary_spaces',
    'align_double_arrow',
    'align_equals',
    'concat_with_spaces',
    'ordered_use',
    'short_array_syntax',
    'single_array_no_trailing_comma',
    'multiline_array_trailing_comma',
    'spaces_cast',
    'return',
    'double_arrow_multiline_whitespaces',
    'extra_empty_lines',
    'function_typehint_space',
    'hash_to_slash_comment',
    'include',
    'lowercase_cast',
    'native_function_casing',
    'new_with_braces',
    'remove_leading_slash_use',
    'short_scalar_cast',
    'single_quote',
    'spaces_after_semicolon',
    'spaces_before_semicolon',
    'spaces_cast',
]);
$config->finder($finder);

return $config;
