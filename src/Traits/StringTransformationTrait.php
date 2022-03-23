<?php declare(strict_types=1);
/**
 * PimcoreDevkitBundle
 * Copyright (c) Lukaschel
 */

namespace Lukaschel\PimcoreDevkitBundle\Traits;

/**
 * Trait StringTransformationTrait
 */
trait StringTransformationTrait
{
    /**
     * @param string $string
     * @param bool   $ucFirst
     *
     * @return string
     */
    public function camelize(string $string, bool $ucFirst = false): string
    {
        $string = trim($string);

        if ($ucFirst) {
            $string = ucfirst($string);
        } else {
            $string = lcfirst($string);
        }

        $string = preg_replace('/^[-_]+/', '', $string);

        $string = preg_replace_callback(
            '/[-_\s\.]+(.)?/u',
            function ($match) {
                if (isset($match[1])) {
                    return strtoupper($match[1]);
                }

                return '';
            },
            $string
        );

        return preg_replace_callback(
            '/[\d]+(.)?/u',
            function ($match) {
                return strtoupper($match[0]);
            },
            $string
        );
    }

    /**
     * @param string $string
     * @param string $replace
     *
     * @return string
     */
    public function hyphen(string $string, string $replace = '-'): string
    {
        return ltrim(strtolower(preg_replace('/([A-Z])/', $replace . '$1', $string)), '-');
    }

    /**
     * @param string $string
     *
     * @return string
     */
    public function natural(string $string): string
    {
        return ltrim(preg_replace('/([A-Z])/', ' $1', $string));
    }
}
