<?php declare(strict_types=1);
/**
 * PimcoreDevkitBundle
 * Copyright (c) Lukaschel
 */

namespace Lukaschel\PimcoreDevkitBundle\Command;

use Exception;
use Lukaschel\PimcoreDevkitBundle\Command\Helper\QuestionHelper;
use Lukaschel\PimcoreDevkitBundle\Command\Validator\CommandValidator;
use Lukaschel\PimcoreDevkitBundle\Model\TwigExtension;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\HttpKernel\Bundle\Bundle;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;

/**
 * Class GenerateTwigExtensionCommand
 */
class GenerateTwigExtensionCommand extends AbstractCommand
{
    /**
     * @return void
     */
    public function configure()
    {
        $this->setCommandDefinitions([
            new InputOption('bundle_name', '', InputOption::VALUE_REQUIRED, 'The name of the bundle to create in'),
            new InputOption('name', '', InputOption::VALUE_REQUIRED, 'The name of the twig extension to create'),
        ]);

        $this
            ->setName('devkit:generate:twig_extension')
            ->setDescription('Generates a Symfony twig extension')
            ->setDefinition($this->getCommandDefinitions())
            ->setHelp(
                <<<EOT
The <info>%command.name%</info> command helps you generates new twig extension
inside bundles.
EOT
            );
    }

    /**
     * @param InputInterface  $input
     * @param OutputInterface $output
     *
     * @throws ContainerExceptionInterface
     * @throws LoaderError
     * @throws NotFoundExceptionInterface
     * @throws RuntimeError
     * @throws SyntaxError
     *
     * @return int
     */
    public function execute(InputInterface $input, OutputInterface $output): int
    {
        /** @var QuestionHelper $questionHelper */
        $questionHelper = $this->getQuestionHelper();

        $questionHelper->writeSection($output, 'Symfony twig extension generation');
        $questionHelper->writeParameterSummary($input, $output, $this->getCommandDefinitions());

        /** @var ConfirmationQuestion $question */
        $question = new ConfirmationQuestion(
            $questionHelper->getQuestion('Do you confirm generation', 'yes', '?'),
            true
        );

        if (!$questionHelper->ask($input, $output, $question)) {
            $output->writeln('<error>Command aborted</error>');

            return Command::FAILURE;
        }

        /** @var TwigExtension $bundle */
        $bundle = new TwigExtension($output, $this->getContainer());
        /** @var bool $response */
        $response = $bundle->create($input)->generate();

        $questionHelper->writeGeneratorSummary($output, $response);

        return Command::SUCCESS;
    }

    /**
     * @param InputInterface  $input
     * @param OutputInterface $output
     *
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     *
     * @return void
     */
    protected function interact(InputInterface $input, OutputInterface $output): void
    {
        /** @var QuestionHelper $questionHelper */
        $questionHelper = $this->getQuestionHelper();
        $questionHelper->writeSection($output, 'Welcome to Symfony twig extension generator');

        $output->writeln([
            '',
            'With the generator you can build your custom twig extension',
            'This command helps you generate them easily.',
            '',
            'First, you need to give the bundle name you want to generate in.',
            'You must use the shortcut notation like <comment>AcmeBlogBundle:Format</comment>',
            '',
        ]);

        /** @var string $bundleName */
        $bundleName = '';
        /** @var string $name */
        $name = '';
        /** @var array $bundleNames */
        $bundleNames = array_keys($this->getContainer()->get('kernel')->getBundles());

        while (true) {
            /** @var Question $question */
            $question = new Question(
                $questionHelper->getQuestion(
                    'Twig extension name',
                    $input->getOption('name')),
                $input->getOption('name')
            );

            $question->setAutocompleterValues($bundleNames);
            $question->setValidator(function ($answer) {
                return CommandValidator::validateTwigExtensionName($answer);
            });

            /** @var string $twigExtension */
            $twigExtension = $questionHelper->ask($input, $output, $question);
            list($bundleName, $name) = $this->parseShortcutNotation($twigExtension);

            try {
                /** @var Bundle $bundle */
                $bundle = $this->getContainer()->get('kernel')->getBundle($bundleName);

                if (!file_exists($bundle->getPath() . '/Twig/Extension/' . $name . 'Extension.php')) {
                    break;
                }

                $output->writeln(sprintf('<bg=red>Twig extension "%s:%s" already exists.</>', $bundleName, $name));
            } catch (Exception $e) {
                $output->writeln(sprintf('<bg=red>Bundle "%s" does not exist.</>', $bundleName));
            }
        }

        $input->setOption('bundle_name', $bundleName);
        $input->setOption('name', $name);
    }
}
