<?php declare(strict_types=1);
/**
 * PimcoreDevkitBundle
 * Copyright (c) Lukaschel
 */

namespace Lukaschel\PimcoreDevkitBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * Class Configuration
 */
class Configuration implements ConfigurationInterface
{
    /**
     * @return TreeBuilder
     */
    public function getConfigTreeBuilder(): TreeBuilder
    {
        return new TreeBuilder('devkit');
    }
}
