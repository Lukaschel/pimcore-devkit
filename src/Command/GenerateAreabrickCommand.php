<?php declare(strict_types=1);
/**
 * PimcoreDevkitBundle
 * Copyright (c) Lukaschel
 */

namespace Lukaschel\PimcoreDevkitBundle\Command;

use Exception;
use Lukaschel\PimcoreDevkitBundle\Command\Helper\QuestionHelper;
use Lukaschel\PimcoreDevkitBundle\Command\Validator\CommandValidator;
use Lukaschel\PimcoreDevkitBundle\Model\Areabrick;
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
 * Class GenerateAreabrickCommand
 */
class GenerateAreabrickCommand extends AbstractCommand
{
    /**
     * @see Command
     */
    public function configure(): void
    {
        $this->setCommandDefinitions([
            new InputOption('bundle_name', '', InputOption::VALUE_REQUIRED, 'The name of the bundle to create in'),
            new InputOption('name', '', InputOption::VALUE_REQUIRED, 'The name of the areabrick to create'),
        ]);

        $this
            ->setName('devkit:generate:areabrick')
            ->setDescription('Generates a Pimcore areabrick')
            ->setDefinition($this->getCommandDefinitions())
            ->setHelp(
                <<<EOT
The <info>%command.name%</info> command helps you generates new areabrick
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

        $questionHelper->writeSection($output, 'Pimcore areabrick generation');
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

        /** @var Areabrick $bundle */
        $bundle = new Areabrick($output, $this->getContainer());
        /** @var bool $response */
        $response = $bundle->create($input)->generate();

        $questionHelper->writeGeneratorSummary($output, $response);

        return Command::SUCCESS;
    }

    /**
     * @param InputInterface  $input
     * @param OutputInterface $output
     *
     * @throws NotFoundExceptionInterface
     * @throws ContainerExceptionInterface
     *
     * @return void
     */
    protected function interact(InputInterface $input, OutputInterface $output): void
    {
        /** @var QuestionHelper $questionHelper */
        $questionHelper = $this->getQuestionHelper();

        $questionHelper->writeSection($output, 'Welcome to Pimcore areabrick generator');

        $output->writeln([
            '',
            'With the generator you can build your custom areabricks',
            'This command helps you generate them easily.',
            '',
            'First, you need to give the bundle name you want to generate in.',
            'You must use the shortcut notation like <comment>AcmeBlogBundle:Blog</comment>',
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
                    'Areabrick name',
                    $input->getOption('name')),
                $input->getOption('name')
            );

            $question->setAutocompleterValues($bundleNames);
            $question->setValidator(function ($answer) {
                return CommandValidator::validateAreabrickName($answer);
            });

            /** @var array|string $areabrick */
            $areabrick = $questionHelper->ask($input, $output, $question);
            list($bundleName, $name) = $this->parseShortcutNotation($areabrick);

            try {
                /** @var Bundle $bundle */
                $bundle = $this->getContainer()->get('kernel')->getBundle($bundleName);

                if (!file_exists($bundle->getPath() . '/Document/Areabrick/' . $name . 'Areabrick.php')) {
                    break;
                }

                $output->writeln(sprintf('<bg=red>Areabrick "%s:%s" already exists.</>', $bundleName, $name));
            } catch (Exception $e) {
                $output->writeln(sprintf('<bg=red>Bundle "%s" does not exist.</>', $bundleName));
            }
        }

        $input->setOption('bundle_name', $bundleName);
        $input->setOption('name', $name);
    }
}
