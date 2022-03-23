<?php declare(strict_types=1);
/**
 * PimcoreDevkitBundle
 * Copyright (c) Lukaschel
 */

namespace Lukaschel\PimcoreDevkitBundle\Model;

/**
 * Interface ModelInterface
 */
interface ModelInterface
{
    /**
     * @return array
     */
    public function getObjectVars(): array;

    /**
     * @return bool
     */
    public function generate(): bool;
}
