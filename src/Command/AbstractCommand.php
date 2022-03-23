<?php declare(strict_types=1);
/**
 * PimcoreDevkitBundle
 * Copyright (c) Lukaschel
 */

namespace Lukaschel\PimcoreDevkitBundle\Command;

use InvalidArgumentException;
use Lukaschel\PimcoreDevkitBundle\Command\Helper\QuestionHelper;
use Pimcore\Console\AbstractCommand as BaseAbstractCommand;
use Psr\Container\ContainerInterface;
use Symfony\Component\Console\Helper\HelperInterface;

/**
 * Class AbstractCommand
 */
abstract class AbstractCommand extends BaseAbstractCommand
{
    /**
     * @var ContainerInterface
     */
    private $container;

    /**
     * @var array
     */
    private $commandDefinitions;

    /**
     * @param ContainerInterface $container
     * @param string|null        $name
     */
    public function __construct(ContainerInterface $container, string $name = null)
    {
        $this->container = $container;
        parent::__construct($name);
    }

    /**
     * @return ContainerInterface
     */
    protected function getContainer(): ContainerInterface
    {
        return $this->container;
    }

    /**
     * @return array
     */
    protected function getCommandDefinitions(): array
    {
        return $this->commandDefinitions;
    }

    /**
     * @param array $commandDefinitions
     *
     * @return void
     */
    protected function setCommandDefinitions(array $commandDefinitions): void
    {
        $this->commandDefinitions = $commandDefinitions;
    }

    /**
     * @param string $shortcut
     *
     * @return array
     */
    protected function parseShortcutNotation(string $shortcut): array
    {
        /** @var string $entity */
        $entity = str_replace('/', '\\', $shortcut);

        if (false === $pos = strpos($entity, ':')) {
            throw new InvalidArgumentException(sprintf('The name must contain a : ("%s" given, expecting something like AcmeBlogBundle:Post)', $entity));
        }

        return [substr($entity, 0, $pos), substr($entity, $pos + 1)];
    }

    /**
     * @param string $route
     *
     * @return array|null
     */
    protected function getPlaceholdersFromRoute(string $route): ?array
    {
        preg_match_all('/{(.*?)}/', $route, $placeholders);

        return $placeholders[1];
    }

    /**
     * @return HelperInterface
     */
    protected function getQuestionHelper(): HelperInterface
    {
        $this->getHelperSet()->set($question = new QuestionHelper());

        return $this->getHelperSet()->get('question');
    }
}
