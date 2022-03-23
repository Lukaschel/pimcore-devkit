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
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;

/**
 * Class Bundle
 */
class Bundle extends ModelEntity
{
    public const MODEL = 'bundle';

    /**
     * @var bool
     */
    protected $shared;

    /**
     * @var string
     */
    protected $namespace;

    /**
     * @var string
     */
    protected $dir;

    /**
     * @var string
     */
    protected $format;

    /**
     * @var bool
     */
    protected $installer;

    /**
     * @var string
     */
    protected $description;

    /**
     * @var string
     */
    protected $authorEmail;

    /**
     * @param OutputInterface    $output
     * @param ContainerInterface $container
     */
    public function __construct(OutputInterface $output, ContainerInterface $container)
    {
        $this->setModel(self::MODEL);
        parent::__construct($output, $container);
    }

    /**
     * @return bool
     */
    public function getShared(): bool
    {
        return $this->shared;
    }

    /**
     * @param bool $shared
     */
    public function setShared(bool $shared): void
    {
        $this->shared = $shared;
    }

    /**
     * @return string
     */
    public function getDir(): string
    {
        return $this->dir;
    }

    /**
     * @param string $dir
     */
    public function setDir(string $dir)
    {
        $this->dir = $dir;
    }

    /**
     * @return string
     */
    public function getRelativDir(): string
    {
        return preg_replace('#/#', '', $this->getDir());
    }

    /**
     * @return string
     */
    public function getNamespace(): string
    {
        return $this->namespace;
    }

    /**
     * @param string $namespace
     */
    public function setNamespace(string $namespace): void
    {
        $this->namespace = $namespace;
    }

    /**
     * @return string
     */
    public function getFormat(): string
    {
        return $this->format;
    }

    /**
     * @param string $format
     */
    public function setFormat(string $format): void
    {
        $this->format = $format;
    }

    /**
     * @return bool
     */
    public function getInstaller(): bool
    {
        return $this->installer;
    }

    /**
     * @param bool $installer
     */
    public function setInstaller(bool $installer): void
    {
        $this->installer = $installer;
    }

    /**
     * @return string
     */
    public function getDescription(): string
    {
        return $this->description;
    }

    /**
     * @param string $description
     */
    public function setDescription(string $description): void
    {
        $this->description = $description;
    }

    /**
     * @return string
     */
    public function getAuthorEmail(): string
    {
        return $this->authorEmail;
    }

    /**
     * @param string $authorEmail
     */
    public function setAuthorEmail(string $authorEmail): void
    {
        $this->authorEmail = $authorEmail;
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
        /** @var string $publicName */
        $publicName = preg_replace('#' . self::MODEL . '#i', '', $this->getName());
        /** @var string $vendor */
        $vendor = preg_replace('#\\\\(.*)#', '', $this->getNamespace());
        /** @var string $dir */
        $dir = $this->getDir() . '/' . $this->getName();

        $this->setBundleName($this->getName());

        if ($this->getShared()) {
            $dir = $this->getDir() . '/' . $vendor . '/' . $this->getName();
        }

        /** @var string $subDir */
        $subDir = $dir . $this->getSubDir();

        $this->addTemplateMapping([
            '/Bundle.php.twig' => $subDir . '/' . $this->camelize($this->getName(), true) . '.php',
            '/DependencyInjection/Extension.php.twig' => $subDir . '/DependencyInjection/' . $this->camelize($publicName, true) . 'Extension.php',
            '/DependencyInjection/Configuration.php.twig' => $subDir . '/DependencyInjection/Configuration.php',
            '/Controller/AbstractController.php.twig' => $subDir . '/Controller/AbstractController.php',
            '/Controller/DefaultController.php.twig' => $subDir . '/Controller/DefaultController.php',
            '/Resources/config/services.yml.twig' => $subDir . '/Resources/config/services.yml',
            '/Resources/config/services/controller.yml.twig' => $subDir . '/Resources/config/services/controller.yml',
            '/Resources/config/pimcore/config.yml.twig' => $subDir . '/Resources/config/pimcore/config.yml',
            '/Resources/config/pimcore/routing.yml.twig' => $subDir . '/Resources/config/pimcore/routing.yml',
            '/Resources/public/js/pimcore/startup.js.twig' => $subDir . '/Resources/public/js/pimcore/startup.js',
            '/Resources/install/.gitignore.twig' => $subDir . '/Resources/install/.gitignore',
            '/README.md.twig' => $dir . '/README.md',
            '/LICENSE.md.twig' => $dir . '/LICENSE.md',
            '/.php-cs-fixer.php.twig' => $dir . '/.php-cs-fixer.php',
        ]);

        if ($this->getShared()) {
            $this->addTemplateMapping([
                '/composer.json.twig' => $dir . '/composer.json',
            ]);
        }

        if ($this->getInstaller()) {
            $this->addTemplateMapping([
                '/Resources/config/services/system.yml.twig' => $subDir . '/Resources/config/services/system.yml',
                '/Tool/Install.php.twig' => $subDir . '/Tool/Install.php',
            ]);
        }

        $this->addTemplateMapping([
            '/Resources/views/base.html.twig.twig' => $subDir . '/Resources/views/base.html.twig',
            '/Resources/views/Default/index.html.twig.twig' => $subDir . '/Resources/views/Default/index.html.twig',
        ]);

        $this->setAdditionalParameter([
            'publicName' => $publicName,
            'vendor' => $this->hyphen($vendor) . '/' . $this->hyphen($this->getRawBundleName()),
            'companyName' => $this->natural($vendor),
        ]);

        /** @var bool $response */
        $response = $this->getGenerator()->generate($this);

        $this->getOutput()->writeln([
            '',
            '<comment>Do not forget to import your bundle into your composer.json psr4 autoload</comment>',
            '',
        ]);

        $this->getOutput()->writeln([
            'Edit the application configuration and make sure',
            sprintf('  you have added the <comment>%s</comment> to the Pimcore bundle search paths: ', $this->getRelativDir()),
            '   <comment>pimcore:</comment>',
            '   <comment>   bundles:</comment>',
            '   <comment>      search_paths:</comment>',
            sprintf('   <comment>          - %s</comment>', $this->getRelativDir()),
        ]);

        return $response;
    }
}
