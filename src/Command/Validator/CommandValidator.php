<?php declare(strict_types=1);
/**
 * PimcoreDevkitBundle
 * Copyright (c) Lukaschel
 */

namespace Lukaschel\PimcoreDevkitBundle\Command\Validator;

use InvalidArgumentException;
use RuntimeException;

/**
 * Class CommandValidator
 */
class CommandValidator
{
    /**
     * @return array
     */
    public static function getReservedWords(): array
    {
        return [
            'abstract',
            'and',
            'array',
            'as',
            'break',
            'callable',
            'case',
            'catch',
            'class',
            'clone',
            'const',
            'continue',
            'declare',
            'default',
            'do',
            'else',
            'elseif',
            'enddeclare',
            'endfor',
            'endforeach',
            'endif',
            'endswitch',
            'endwhile',
            'extends',
            'final',
            'finally',
            'for',
            'foreach',
            'function',
            'global',
            'goto',
            'if',
            'implements',
            'interface',
            'instanceof',
            'insteadof',
            'namespace',
            'new',
            'or',
            'private',
            'protected',
            'public',
            'static',
            'switch',
            'throw',
            'trait',
            'try',
            'use',
            'var',
            'while',
            'xor',
            'yield',
            '__CLASS__',
            '__DIR__',
            '__FILE__',
            '__LINE__',
            '__FUNCTION__',
            '__METHOD__',
            '__NAMESPACE__',
            '__TRAIT__',
            '__halt_compiler',
            'die',
            'echo',
            'empty',
            'exit',
            'eval',
            'include',
            'include_once',
            'isset',
            'list',
            'require',
            'require_once',
            'return',
            'print',
            'unset',
        ];
    }

    /**
     * @param string $namespace
     * @param bool   $requireVendorNamespace
     *
     * @return string
     */
    public static function validateBundleNamespace(string $namespace, bool $requireVendorNamespace = true): string
    {
        if (!preg_match('/Bundle$/', $namespace)) {
            throw new InvalidArgumentException('The namespace must end with Bundle.');
        }

        $namespace = strtr($namespace, '/', '\\');

        if (!preg_match('/^(?:[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*\\\?)+$/', $namespace)) {
            throw new InvalidArgumentException('The namespace contains invalid characters.');
        }

        foreach (explode('\\', $namespace) as $word) {
            if (in_array(strtolower($word), self::getReservedWords())) {
                throw new InvalidArgumentException(sprintf('The namespace cannot contain PHP reserved words ("%s").', $word));
            }
        }

        if ($requireVendorNamespace &&
            strpos($namespace, '\\') === false
        ) {
            throw new InvalidArgumentException(sprintf('The namespace must contain a vendor namespace (e.g. "VendorName\%s" instead of simply "%s").' . "\n\n" . 'If you\'ve specified a vendor namespace, did you forget to surround it with quotes (init:bundle "Acme\BlogBundle")?', $namespace, $namespace));
        }

        return $namespace;
    }

    /**
     * @param string $name
     *
     * @return string
     */
    public static function validateBundleName(string $name): string
    {
        if (!preg_match('/^[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*$/', $name)) {
            throw new InvalidArgumentException(sprintf('The bundle name %s contains invalid characters.', $name));
        }

        if (!preg_match('/Bundle$/', $name)) {
            throw new InvalidArgumentException('The bundle name must end with Bundle.');
        }

        return $name;
    }

    /**
     * @param string $name
     *
     * @return string
     */
    public static function validateFormatName(string $name): string
    {
        if (!$name) {
            throw new RuntimeException('Please enter a configuration format.');
        }

        $name = strtolower($name);

        if ($name == 'yaml') {
            $name = 'yml';
        }

        if (!in_array($name, ['yml', 'annotation'])) {
            throw new RuntimeException(sprintf('Format "%s" is not supported.', $name));
        }

        return $name;
    }

    /**
     * @param string $email
     *
     * @return string
     */
    public static function validateEmail(string $email): string
    {
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new InvalidArgumentException(sprintf('The author email "%s" is not a valid email', $email));
        }

        return $email;
    }

    /**
     * @param string $name
     *
     * @throws InvalidArgumentException
     *
     * @return string
     */
    public static function validateEntityName(string $name): string
    {
        if (!preg_match('{^[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*:[a-zA-Z0-9_\x7f-\xff\\\/]+$}', $name)) {
            throw new InvalidArgumentException(sprintf('The entity name isn\'t valid ("%s" given, expecting something like AcmeBlogBundle:Blog/Post)', $name));
        }

        return $name;
    }

    /**
     * @param string $name
     *
     * @return string
     */
    public static function validateControllerName(string $name): string
    {
        try {
            self::validateEntityName($name);
        } catch (InvalidArgumentException $e) {
            throw new InvalidArgumentException(sprintf('The controller name must contain a : ("%s" given, expecting something like AcmeBlogBundle:Post)', $name));
        }

        return $name;
    }

    /**
     * @param string|null $name
     * @param array       $actions
     *
     * @return string|null
     */
    public static function validateActionName(array $actions, string $name = null): ?string
    {
        if ($name === null) {
            return $name;
        }

        if (isset($actions[$name])) {
            throw new InvalidArgumentException(sprintf('Action "%s" is already defined', $name));
        }

        if (substr($name, -6) != 'Action') {
            throw new InvalidArgumentException(sprintf('Name "%s" is not suffixed by Action', $name));
        }

        return $name;
    }

    /**
     * @param string $name
     *
     * @return string
     */
    public static function validateTwigExtensionName(string $name): string
    {
        try {
            self::validateEntityName($name);
        } catch (InvalidArgumentException $e) {
            throw new InvalidArgumentException(sprintf('The twig extension name must contain a : ("%s" given, expecting something like AcmeBlogBundle:Post)', $name));
        }

        return $name;
    }

    /**
     * @param string $name
     *
     * @return string
     */
    public static function validateEventSubscriberName(string $name): string
    {
        try {
            self::validateEntityName($name);
        } catch (InvalidArgumentException $e) {
            throw new InvalidArgumentException(sprintf('The event listener name must contain a : ("%s" given, expecting something like AcmeBlogBundle:Post)', $name));
        }

        return $name;
    }

    /**
     * @param string $name
     *
     * @return string
     */
    public static function validateAreabrickName(string $name): string
    {
        try {
            self::validateEntityName($name);
        } catch (InvalidArgumentException $e) {
            throw new InvalidArgumentException(sprintf('The areabrick name must contain a : ("%s" given, expecting something like AcmeBlogBundle:Blog)', $name));
        }

        return $name;
    }

    /**
     * @param string $name
     *
     * @return string
     */
    public static function validateCommandName(string $name): string
    {
        try {
            self::validateEntityName($name);
        } catch (InvalidArgumentException $e) {
            throw new InvalidArgumentException(sprintf('The command name must contain a : ("%s" given, expecting something like AcmeBlogBundle:Command)', $name));
        }

        return $name;
    }
}
