<?php declare(strict_types=1);

/**
 * PimcoreDevkitBundle
 * Copyright (c) Lukaschel
 */

namespace Lukaschel\PimcoreDevkitBundle\Command;

use Exception;
use Lukaschel\PimcoreDevkitBundle\Command\Helper\QuestionHelper;
use Lukaschel\PimcoreDevkitBundle\Command\Validator\CommandValidator;
use Lukaschel\PimcoreDevkitBundle\Model\Controller;
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
 * Class GenerateControllerCommand
 */
class GenerateControllerCommand extends AbstractCommand
{
    /**
     * @return void
     */
    protected function configure(): void
    {
        $this->setCommandDefinitions([
            new InputOption('bundle_name', '', InputOption::VALUE_REQUIRED, 'The name of the bundle to create in'),
            new InputOption('name', '', InputOption::VALUE_REQUIRED, 'The name of the controller to create'),
            new InputOption('route_format', '', InputOption::VALUE_REQUIRED, 'The format that is used for the routing (yml, annotation)', 'annotation'),
            new InputOption('actions', '', InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY, 'The actions in the controller'),
        ]);

        $this
            ->setName('devkit:generate:controller')
            ->setDescription('Generates a Symfony controller')
            ->setDefinition($this->getCommandDefinitions())
            ->setHelp(
                <<<EOT
The <info>%command.name%</info> command helps you generates new controllers
inside bundles.

By default, the command interacts with the developer to tweak the generation.
Any passed option will be used as a default value for the interaction
(<comment>--controller</comment> is the only one needed if you follow the conventions):

<info>php %command.full_name% --controller=AcmeBlogBundle:Post</info>

If you want to disable any user interaction, use <comment>--no-interaction</comment>
but don't forget to pass all needed options:

<info>php %command.full_name% --controller=AcmeBlogBundle:Post --no-interaction</info>

Every generated file is based on a template. There are default templates but they can
be overridden by placing custom templates in one of the following locations, by order of priority:

<info>BUNDLE_PATH/Resources/SensioGeneratorBundle/skeleton/controller
APP_PATH/Resources/SensioGeneratorBundle/skeleton/controller</info>

You can check https://github.com/sensio/SensioGeneratorBundle/tree/master/Resources/skeleton
in order to know the file structure of the skeleton
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

        $questionHelper->writeSection($output, 'Symfony controller generation');
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

        /** @var Controller $bundle */
        $bundle = new Controller($output, $this->getContainer());
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

        $questionHelper->writeSection($output, 'Welcome to the Symfony controller generator');

        $output->writeln([
            '',
            'Every page, and even sections of a page, are rendered by a <comment>controller</comment>.',
            'This command helps you generate them easily.',
            '',
            'First, you need to give the controller name you want to generate.',
            'You must use the shortcut notation like <comment>AcmeBlogBundle:Post</comment>',
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
            $question = new Question($questionHelper->getQuestion('Controller name', $input->getOption('name')), $input->getOption('name'));

            $question->setAutocompleterValues($bundleNames);
            $question->setValidator(function ($answer) {
                return CommandValidator::validateControllerName($answer);
            });

            /** @var string $controller */
            $controller = $questionHelper->ask($input, $output, $question);
            list($bundleName, $name) = $this->parseShortcutNotation($controller);

            try {
                /** @var Bundle $bundle */
                $bundle = $this->getContainer()->get('kernel')->getBundle($bundleName);

                if (!file_exists($bundle->getPath() . '/Controller/' . $name . 'Controller.php')) {
                    break;
                }

                $output->writeln(sprintf('<bg=red>Controller "%s:%s" already exists.</>', $bundleName, $name));
            } catch (Exception $e) {
                $output->writeln(sprintf('<bg=red>Bundle "%s" does not exist.</>', $bundleName));
            }
        }

        $input->setOption('bundle_name', $bundleName);
        $input->setOption('name', $name);

        /** @var string $defaultFormat */
        $defaultFormat = ($input->getOption('route_format') !== null ? $input->getOption('route_format') : 'annotation');

        $output->writeln([
            '',
            'Determine the format to use for the routing.',
            '',
        ]);

        /** @var Question $question */
        $question = new Question($questionHelper->getQuestion('Routing format (yml, annotation)', $defaultFormat), $defaultFormat);

        $question->setValidator(function ($answer) {
            return CommandValidator::validateFormatName($answer);
        });

        /** @var string $routeFormat */
        $routeFormat = $questionHelper->ask($input, $output, $question);

        $input->setOption('route_format', $routeFormat);

        $output->writeln([
            '',
            'Instead of starting with a blank controller, you can add some actions now. An action',
            'is a PHP function or method that executes, for example, when a given route is matched.',
            'Actions should be suffixed by <comment>Action</comment>.',
            '',
        ]);

        /** @var array $actions */
        $actions = [];
        while (true) {
            $output->writeln('');

            /** @var Question $question */
            $question = new Question($questionHelper->getQuestion('New action name (press <return> to stop adding actions)', null), null);

            $question->setValidator(function ($answer) use ($actions) {
                return CommandValidator::validateActionName($actions, $answer);
            });

            /** @var string $actionName */
            $actionName = $questionHelper->ask($input, $output, $question);

            if (!$actionName) {
                break;
            }

            /** @var Question $question */
            $question = new Question($questionHelper->getQuestion('Action route', '/' . substr($actionName, 0, -6)), '/' . substr($actionName, 0, -6));
            /** @var string $actionRoute */
            $actionRoute = $questionHelper->ask($input, $output, $question);
            /** @var mixed $actionPlaceholders */
            $actionPlaceholders = $this->getPlaceholdersFromRoute($actionRoute);

            /** @var string $defaultTemplate */
            $defaultTemplate = strtolower(preg_replace(['/([A-Z]+)([A-Z][a-z])/', '/([a-z\d])([A-Z])/'], ['\\1_\\2', '\\1_\\2'], strtr(substr($actionName, 0, -6), '_', '.')))
                . '.html.twig';
            /** @var Question $question */
            $question = new Question($questionHelper->getQuestion('Template name (optional)', $defaultTemplate), $defaultTemplate);
            /** @var string $actionTemplate */
            $actionTemplate = $questionHelper->ask($input, $output, $question);

            $actions[$actionName] = [
                'name' => $actionName,
                'route' => $actionRoute,
                'placeholders' => $actionPlaceholders,
                'template' => $actionTemplate,
            ];
        }

        $input->setOption('actions', $actions);
    }
}
