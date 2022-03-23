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
 * Class EventSubscriber
 */
class EventSubscriber extends ModelEntity
{
    public const MODEL = 'event_subscriber';

    /**
     * EventListener constructor.
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

        if (!file_exists($dir . '/EventSubscriber/AbstractSubscriber.php')) {
            $this->addTemplateMapping([
                '/EventSubscriber/AbstractSubscriber.php.twig' => $dir . '/EventSubscriber/AbstractSubscriber.php',
            ]);
        }

        $this->addTemplateMapping([
            '/EventSubscriber/EventSubscriber.php.twig' => $dir . '/EventSubscriber/' . $this->camelize($this->getName(), true) . 'Subscriber.php',
        ]);

        if (!file_exists($dir . '/Resources/config/services/event_subscriber.yml')) {
            $this->addTemplateMapping([
                '/Resources/config/services/event_subscriber.yml.twig' => $dir . '/Resources/config/services/event_subscriber.yml',
            ]);
        }

        $this->setAdditionalParameter([
            'namespace' => $bundle->getNamespace(),
        ]);

        /** @var bool $response */
        $response = $this->getGenerator()->generate($this);

        $this->getOutput()->writeln([
            '',
            sprintf('<comment>Do not forget to import the Yaml configuration files under %s</comment>',
                $dir . '/Resources/config/services/event_subscriber.yml'
            ),
            '',
        ]);

        return $response;
    }
}
