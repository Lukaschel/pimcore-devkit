<?php declare(strict_types=1);

use PhpCsFixer\Config;
use PhpCsFixer\Finder;

/** @var Finder $finder */
$finder = Finder::create()
    ->in(__DIR__);

/** @var string $header */
$header = <<<EOF
PimcoreDevkitBundle
Copyright (c) Lukaschel
EOF;
/** @var Config $config */
$config = new Config();

return $config
    ->setUsingCache(false)
    ->setRules([
        '@PSR2' => true,
        '@Symfony' => true,
        'header_comment' => ['header' => $header, 'separate' => 'bottom', 'comment_type' => 'PHPDoc'],
        'no_useless_else' => true,
        'no_useless_return' => true,
        'ordered_class_elements' => true,
        'ordered_imports' => true,
        'phpdoc_order' => true,
        'phpdoc_summary' => false,
        'no_superfluous_phpdoc_tags' => false,
        'blank_line_after_opening_tag' => false,
        'linebreak_after_opening_tag' => false,
        'concat_space' => ['spacing' => 'one'],
        'array_syntax' => ['syntax' => 'short'],
        'yoda_style' => ['equal' => false, 'identical' => false, 'less_and_greater' => false],
    ])
    ->setFinder($finder);
