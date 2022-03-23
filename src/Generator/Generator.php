<?php declare(strict_types=1);
/**
 * PimcoreDevkitBundle
 * Copyright (c) Lukaschel
 */

namespace Lukaschel\PimcoreDevkitBundle\Generator;

use Lukaschel\PimcoreDevkitBundle\Model\ModelEntity;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Filesystem;
use Twig\Environment;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;
use Twig\Loader\FilesystemLoader;

/**
 * Class Generator
 */
class Generator
{
    public const MODE_CREATE = 1;
    public const MODE_APPEND = 2;

    /**
     * @var ContainerInterface
     */
    private $container;

    /**
     * @var OutputInterface
     */
    private $output;

    /**
     * @param ModelEntity $entity
     *
     * @throws ContainerExceptionInterface
     * @throws LoaderError
     * @throws NotFoundExceptionInterface
     * @throws RuntimeError
     * @throws SyntaxError
     *
     * @return bool
     */
    public function generate(ModelEntity $entity): bool
    {
        if (empty($entity->getObjectVars()) ||
            (
                empty($entity->getTemplateMapping()) &&
                empty($entity->getAppendingTemplateMapping())
            ) ||
            empty($entity->getParameters())
        ) {
            return false;
        }

        /* @var ContainerInterface container */
        $this->container = $entity->getContainer();

        /* @var OutputInterface output */
        $this->output = $entity->getOutput();

        foreach ($entity->getTemplateMapping() as $template => $target) {
            $templatePath = pathinfo($template);
            $this->renderFile($templatePath['basename'], $target, $entity->getSkeletonDir() . '/' . ltrim($templatePath['dirname'], '/'), $entity->getParameters());
        }

        foreach ($entity->getAppendingTemplateMapping() as $template => $target) {
            $templatePath = pathinfo($template);
            $this->appendFile($templatePath['basename'], $target, $entity->getSkeletonDir() . '/' . ltrim($templatePath['dirname'], '/'), $entity->getParameters());
        }

        return true;
    }

    /**
     * @param string $dir
     * @param int    $mode
     * @param bool   $recursive
     *
     * @return void
     */
    public function mkdir(string $dir, int $mode = 0777, bool $recursive = true): void
    {
        if (!is_dir($dir)) {
            mkdir($dir, $mode, $recursive);
            self::writeln(sprintf('  <fg=green>created</> %s', self::relativizePath($dir)));
        }
    }

    /**
     * @param string $filename
     * @param string $content
     * @param int    $mode
     *
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     *
     * @return bool
     */
    public function dump(string $filename, string $content, int $mode = self::MODE_CREATE): bool
    {
        if (file_exists($filename)) {
            $this->writeln(sprintf('  <fg=yellow>updated</> %s', self::relativizePath($filename)));
        } else {
            $this->writeln(sprintf('  <fg=green>created</> %s', self::relativizePath($filename)));
        }

        switch ($mode) {
            case self::MODE_CREATE:
                $this->getFilesystem()->dumpFile($filename, $content);

                return true;
            case self::MODE_APPEND:
                $this->getFilesystem()->appendToFile($filename, $content);

                return true;
        }

        return false;
    }

    /**
     * @return ContainerInterface
     */
    protected function getContainer(): ContainerInterface
    {
        return $this->container;
    }

    /**
     * @return OutputInterface
     */
    protected function getOutput(): OutputInterface
    {
        return $this->output;
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     *
     * @return Filesystem|null
     */
    protected function getFilesystem(): ?Filesystem
    {
        return $this->getContainer()->get('filesystem');
    }

    /**
     * @param string $template
     * @param string $skeletonDir
     * @param array  $parameters
     *
     * @throws RuntimeError
     * @throws SyntaxError
     * @throws LoaderError
     *
     * @return string
     */
    protected function render(string $template, string $skeletonDir, array $parameters): string
    {
        return $this->getTwigEnvironment($skeletonDir)->render($template, $parameters);
    }

    /**
     * @param string $skeletonDir
     *
     * @return Environment
     */
    protected function getTwigEnvironment(string $skeletonDir): Environment
    {
        return new Environment(new FilesystemLoader($skeletonDir), [
            'debug' => true,
            'cache' => false,
            'strict_variables' => true,
            'autoescape' => false,
        ]);
    }

    /**
     * @param string $template
     * @param string $target
     * @param string $skeletonDir
     * @param array  $parameters
     *
     * @throws ContainerExceptionInterface
     * @throws LoaderError
     * @throws NotFoundExceptionInterface
     * @throws RuntimeError
     * @throws SyntaxError
     *
     * @return bool
     */
    protected function renderFile(string $template, string $target, string $skeletonDir, array $parameters): bool
    {
        $this->mkdir(dirname($target));

        return $this->dump($target, $this->render($template, $skeletonDir, $parameters));
    }

    /**
     * @param string $template
     * @param string $target
     * @param string $skeletonDir
     * @param array  $parameters
     *
     * @throws ContainerExceptionInterface
     * @throws LoaderError
     * @throws NotFoundExceptionInterface
     * @throws RuntimeError
     * @throws SyntaxError
     *
     * @return bool
     */
    protected function appendFile(string $template, string $target, string $skeletonDir, array $parameters): bool
    {
        return $this->dump($target, $this->render($template, $skeletonDir, $parameters), self::MODE_APPEND);
    }

    /**
     * @param string $message
     *
     * @return void
     */
    private function writeln(string $message): void
    {
        $this->getOutput()->writeln($message);
    }

    /**
     * @param string $absolutePath
     *
     * @return string
     */
    private function relativizePath(string $absolutePath): string
    {
        $relativePath = str_replace(getcwd(), '.', $absolutePath);

        return is_dir($absolutePath) ? rtrim($relativePath, '/') . '/' : $relativePath;
    }
}
