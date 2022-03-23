<?php declare(strict_types=1);
/**
 * PimcoreDevkitBundle
 * Copyright (c) Lukaschel
 */

namespace Lukaschel\PimcoreDevkitBundle\Model;

use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\HttpKernel\Bundle\Bundle;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;

/**
 * Class Areabrick
 */
class Areabrick extends ModelEntity
{
    public const MODEL = 'areabrick';

    /**
     * Command constructor.
     *
     * @param OutputInterface    $output
     * @param ContainerInterface $container
     */
    public function __construct(OutputInterface $output, ContainerInterface $container)
    {
        $this->setModel(self::MODEL);
        parent::__construct($output, $container);
    }

    /**
     * @throws LoaderError
     * @throws NotFoundExceptionInterface
     * @throws RuntimeError
     * @throws SyntaxError
     * @throws ContainerExceptionInterface
     *
     * @return bool
     */
    public function generate(): bool
    {
        /** @var Bundle $bundle */
        $bundle = $this->getContainer()->get('kernel')->getBundle($this->getBundleName());

        /** @var string $dir */
        $dir = $bundle->getPath();

        if (!file_exists($dir . '/Document/Areabrick/AbstractAreabrick.php')) {
            $this->addTemplateMapping([
                '/Document/Areabrick/AbstractAreabrick.php.twig' => $dir . '/Document/Areabrick/AbstractAreabrick.php',
            ]);
        }

        $this->addTemplateMapping([
            '/Document/Areabrick/Areabrick.php.twig' => $dir . '/Document/Areabrick/' . $this->camelize($this->getName(), true) . 'Areabrick.php',
            '/Resources/config/pimcore/areas/services.yml.twig' => $dir . '/Resources/config/pimcore/areas/' . $this->camelize($this->getName(), true) . 'Areabrick.yml',
        ]);

        $this->addTemplateMapping([
            '/Resources/views/Areas/view.html.twig.twig' => $dir . '/Resources/views/Areas/' . $this->camelize($this->getBundleName(), true) . '_' . $this->camelize($this->getName(), true) . '/view.html.twig',
        ]);

        $this->setAdditionalParameter([
            'namespace' => $bundle->getNamespace(),
            'backendName' => $this->natural($this->getName()),
        ]);

        /** @var bool $response */
        $response = $this->getGenerator()->generate($this);

        $this->getOutput()->writeln([
            '',
            sprintf('<comment>Do not forget to import the Yaml configuration files under %sAreabick</comment>',
                $dir . '/Resources/config/pimcore/areas/' . $this->camelize($this->getName(), true)
            ),
            '',
        ]);

        return $response;
    }
}
