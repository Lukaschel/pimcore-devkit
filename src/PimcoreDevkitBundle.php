<?php declare(strict_types=1);
/**
 * PimcoreDevkitBundle
 * Copyright (c) Lukaschel
 */

namespace Lukaschel\PimcoreDevkitBundle;

use Exception;
use Pimcore\Extension\Bundle\AbstractPimcoreBundle;
use Pimcore\Extension\Bundle\Traits\PackageVersionTrait;

/**
 * Class PimcoreDevkitBundle
 */
class PimcoreDevkitBundle extends AbstractPimcoreBundle
{
    use PackageVersionTrait {
        getVersion as traitGetVersion;
    }

    public const PACKAGE_NAME = 'lukaschel/pimcore-devkit';
    public const SUB_DIR = '/src';

    /**
     * @return string
     */
    public function getVersion(): string
    {
        try {
            return $this->traitGetVersion();
        } catch (Exception $e) {
            return 'local';
        }
    }

    /**
     * @return string
     */
    protected function getComposerPackageName(): string
    {
        return self::PACKAGE_NAME;
    }
}
