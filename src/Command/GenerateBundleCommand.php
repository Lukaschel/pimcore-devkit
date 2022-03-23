<?php declare(strict_types=1);
/**
 * PimcoreDevkitBundle
 * Copyright (c) Lukaschel
 */

namespace Lukaschel\PimcoreDevkitBundle\Command;

use Lukaschel\PimcoreDevkitBundle\Command\Helper\QuestionHelper;
use Lukaschel\PimcoreDevkitBundle\Command\Validator\CommandValidator;
use Lukaschel\PimcoreDevkitBundle\Model\Bundle;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Console\Question\Question;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;

/**
 * Class GenerateBundleCommand
 */
class GenerateBundleCommand extends AbstractCommand
{
    /**
     * @return void
     */
    protected function configure(): void
    {
        $this->setCommandDefinitions([
            new InputOption('shared', '', InputOption::VALUE_NONE, 'Are you planning on sharing this bundle across multiple applications?'),
            new InputOption('namespace', '', InputOption::VALUE_REQUIRED, 'The namespace of the bundle to create'),
            new InputOption('name', '', InputOption::VALUE_REQUIRED, 'The bundle name'),
            new InputOption('dir', '', InputOption::VALUE_REQUIRED, 'The directory where to create the bundle', 'bundles/'),
            new InputOption('format', '', InputOption::VALUE_REQUIRED, 'Use the format for configuration files (yml or annotation)'),
            new InputOption('installer', '', InputOption::VALUE_NONE, 'The installer option'),
            new InputOption('description', '', InputOption::VALUE_REQUIRED, 'The pimcore bundle description'),
            new InputOption('author_email', '', InputOption::VALUE_REQUIRED, 'The pimcore bundle author name'),
        ]);

        $this
            ->setName('devkit:generate:bundle')
            ->setDescription('Generates a Pimcore bundle')
            ->setDefinition($this->getCommandDefinitions())
            ->setHelp(
                <<<EOT
The <info>%command.name%</info> command helps you generates new Pimcore bundles. If you need to create a normal Symfony
bundle, please use the generate:bundle command without pimcore: prefix.

By default, the command interacts with the developer to tweak the generation.
Any passed option will be used as a default value for the interaction
(<comment>--namespace</comment> is the only one needed if you follow the
conventions):

<info>php %command.full_name% --namespace=Acme/BlogBundle</info>

Note that you can use <comment>/</comment> instead of <comment>\\ </comment>for the namespace delimiter to avoid any
problems.

If you want to disable any user interaction, use <comment>--no-interaction</comment> but don't forget to pass all needed options:

<info>php %command.full_name% --namespace=Acme/BlogBundle --dir=bundles [--bundle-name=...] --no-interaction</info>

Note that the bundle namespace must end with "Bundle".
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
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        /** @var QuestionHelper $questionHelper */
        $questionHelper = $this->getQuestionHelper();

        $questionHelper->writeSection($output, 'Pimcore bundle generation');
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

        /** @var Bundle $bundle */
        $bundle = new Bundle($output, $this->getContainer());
        /** @var bool $response */
        $response = $bundle->create($input)->generate();

        $questionHelper->writeGeneratorSummary($output, $response);

        return Command::SUCCESS;
    }

    /**
     * @param InputInterface  $input
     * @param OutputInterface $output
     *
     * @return void
     */
    protected function interact(InputInterface $input, OutputInterface $output): void
    {
        /** @var QuestionHelper $questionHelper */
        $questionHelper = $this->getQuestionHelper();

        $questionHelper->writeSection($output, 'Welcome to the Pimcore bundle generator!');

        /** @var bool $shared */
        $shared = $input->getOption('shared');

        /** @var ConfirmationQuestion $question */
        $question = new ConfirmationQuestion($questionHelper->getQuestion(
            'Are you planning on sharing this Pimcore bundle across multiple applications?',
            $shared ? 'yes' : 'no'
        ), $shared);

        $shared = $questionHelper->ask($input, $output, $question);
        $input->setOption('shared', $shared);

        /** @var string $namespace */
        $namespace = $input->getOption('namespace');

        $output->writeln([
            '',
            'Your application code must be written in <comment>bundles</comment>. This command helps',
            'you generate them easily.',
            '',
        ]);

        /** @var bool $askForBundleName */
        $askForBundleName = true;

        if ($shared) {
            $output->writeln([
                'Each bundle is hosted under a namespace (like <comment>Acme/BlogBundle</comment>).',
                'The namespace should begin with a "vendor" name like your company name, your',
                'project name, or your client name, followed by one or more optional category',
                'sub-namespaces, and it should end with the bundle name itself',
                '(which must have <comment>Bundle</comment> as a suffix).',
                '',
                'See http://symfony.com/doc/current/cookbook/bundles/best_practices.html#bundle-name for more',
                'details on bundle naming conventions.',
                '',
                'Use <comment>/</comment> instead of <comment>\\ </comment>for the namespace delimiter to avoid any problems.',
                '',
            ]);

            /** @var Question $question */
            $question = new Question($questionHelper->getQuestion(
                'Bundle namespace',
                $namespace
            ), $namespace);

            $question->setValidator(function ($answer) {
                return CommandValidator::validateBundleNamespace($answer, true);
            });

            $namespace = $questionHelper->ask($input, $output, $question);
        } else {
            $output->writeln([
                'Give your bundle a descriptive name, like <comment>BlogBundle</comment>.',
            ]);

            /** @var Question $question */
            $question = new Question($questionHelper->getQuestion(
                'Bundle name',
                $namespace
            ), $namespace);

            $question->setValidator(function ($inputNamespace) {
                return CommandValidator::validateBundleNamespace($inputNamespace, false);
            });

            $namespace = $questionHelper->ask($input, $output, $question);

            if (strpos($namespace, '\\') === false) {
                // this is a bundle name (FooBundle) not a namespace (Acme\FooBundle)
                // so this is the bundle name (and it is also the namespace)
                $input->setOption('name', $namespace);
                $askForBundleName = false;
            }
        }

        $input->setOption('namespace', $namespace);

        if ($askForBundleName) {
            /** @var string $bundle */
            $bundle = $input->getOption('name');

            if (!$bundle) {
                $bundle = strtr($namespace, ['\\Bundle\\' => '']);
                $bundle = substr($bundle, strpos($bundle, '\\') + 1);
            }

            $output->writeln([
                '',
                'In your code, a bundle is often referenced by its name. It can be the',
                'concatenation of all namespace parts but it\'s really up to you to come',
                'up with a unique name (a good practice is to start with the vendor name).',
                'Based on the namespace, we suggest <comment>' . $bundle . '</comment>.',
                '',
            ]);

            /** @var Question $question */
            $question = new Question($questionHelper->getQuestion(
                'Bundle name',
                $bundle
            ), $bundle);

            $question->setValidator(function ($inputBundleName) {
                return CommandValidator::validateBundleName($inputBundleName);
            });

            $bundle = $questionHelper->ask($input, $output, $question);
            $input->setOption('name', $bundle);
        }

        /** @var string $dir */
        $dir = $input->getOption('dir');

        $output->writeln([
            '',
            'Bundles are usually generated into the <info>bundles/</info> directory. Unless you\'re',
            'doing something custom, hit enter to keep this default!',
            '',
        ]);

        /** @var Question $question */
        $question = new Question($questionHelper->getQuestion(
            'Target Directory',
            $dir
        ), $dir);

        $dir = $questionHelper->ask($input, $output, $question);
        $input->setOption('dir', $dir);
        $input->setOption('format', 'annotation');

        /** @var string $format */
        $format = $input->getOption('format');
        $format = $format ? 'yml' : 'annotation';

        $output->writeln([
            '',
            'What format do you want to use for your generated configuration?',
            '',
        ]);

        /** @var Question $question */
        $question = new Question($questionHelper->getQuestion(
            'Configuration format (annotation, yml)',
            $format
        ), $format);

        $question->setValidator(function ($answer) {
            return CommandValidator::validateFormatName($answer);
        });

        $question->setAutocompleterValues([
            'annotation',
            'yml',
        ]);

        $format = $questionHelper->ask($input, $output, $question);
        $input->setOption('format', $format);

        /** @var bool $installer */
        $installer = $input->getOption('installer');

        $output->writeln([
            '',
            'A installer is useful to install required data objects, folders, translations...',
            '',
        ]);

        /** @var ConfirmationQuestion $question */
        $question = new ConfirmationQuestion($questionHelper->getQuestion(
            'Do you need a installer for your bundle?',
            $installer ? 'yes' : 'no'
        ), $installer);

        $installer = $questionHelper->ask($input, $output, $question);
        $input->setOption('installer', $installer);

        $output->writeln([
            '',
            'Your Pimcore bundle needs a description!',
            '',
        ]);

        /** @var string $description */
        $description = '';

        while (true) {
            /** @var Question $question */
            $question = new Question(
                $questionHelper->getQuestion(
                    'Bundle description',
                    $input->getOption('description')),
                $input->getOption('description')
            );

            $description = $questionHelper->ask($input, $output, $question);

            if (!empty($description)) {
                break;
            }

            $output->writeln('<bg=red>Bundle description can not be empty.</>');
        }

        $input->setOption('description', $description);

        $output->writeln([
            '',
            'Your Pimcore bundle needs a author email!',
            '',
        ]);

        /** @var string $authorEmail */
        $authorEmail = '';

        while (true) {
            /** @var Question $question */
            $question = new Question(
                $questionHelper->getQuestion(
                    'Bundle author email',
                    $input->getOption('author_email')),
                $input->getOption('author_email')
            );

            $question->setValidator(function ($inputAuthorEmail) {
                return CommandValidator::validateEmail($inputAuthorEmail);
            });

            $authorEmail = $questionHelper->ask($input, $output, $question);

            if (!empty($authorEmail)) {
                break;
            }

            $output->writeln('<bg=red>Bundle author email can not be empty.</>');
        }

        $input->setOption('author_email', $authorEmail);
    }
}
