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
 * Class Controller
 */
class Controller extends ModelEntity
{
    public const MODEL = 'controller';

    /**
     * @var string
     */
    protected $routeFormat;

    /**
     * @var array
     */
    protected $actions;

    /**
     * Controller constructor.
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
     * @return string
     */
    public function getRouteFormat(): string
    {
        return $this->routeFormat;
    }

    /**
     * @param string $routeFormat
     */
    public function setRouteFormat(string $routeFormat): void
    {
        $this->routeFormat = $routeFormat;
    }

    /**
     * @return array
     */
    public function getActions(): array
    {
        return $this->actions;
    }

    /**
     * @param array $actions
     */
    public function setActions(array $actions): void
    {
        $this->actions = $actions;
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws LoaderError
     * @throws NotFoundExceptionInterface
     * @throws RuntimeError
     * @throws SyntaxError
     *
     * @return bool
     */
    public function generate(): bool
    {
        /** @var Bundle $bundle */
        $bundle = $this->getContainer()->get('kernel')->getBundle($this->getBundleName());

        /** @var string $dir */
        $dir = $bundle->getPath();

        if (!file_exists($dir . '/Controller/AbstractController.php')) {
            $this->addTemplateMapping([
                '/Controller/AbstractController.php.twig' => $dir . '/Controller/AbstractController.php',
            ]);
        }

        $this->addTemplateMapping([
            '/Controller/Controller.php.twig' => $dir . '/Controller/' . $this->camelize($this->getName(), true) . 'Controller.php',
        ]);

        if (!empty($this->getActions())) {
            foreach ($this->getActions() as $action) {
                $this->addTemplateMapping([
                    '/Resources/views/Controller/index.html.twig.twig' => $dir . '/Resources/views/' . $this->getName() . '/' . $action['template'],
                ]);
            }
        }

        if ($this->getRouteFormat() == 'yml') {
            $this->addAppendingTemplateMapping([
                '/Resources/config/pimcore/routing.yml.twig' => $dir . '/Resources/config/pimcore/routing.yml',
            ]);
        }

        $this->setAdditionalParameter([
            'namespace' => $bundle->getNamespace(),
            'rawBundleName' => $this->getRawBundleName(),
        ]);

        return $this->getGenerator()->generate($this);
    }
}
