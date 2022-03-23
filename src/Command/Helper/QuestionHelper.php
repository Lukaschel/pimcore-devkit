<?php declare(strict_types=1);
/**
 * PimcoreDevkitBundle
 * Copyright (c) Lukaschel
 */

namespace Lukaschel\PimcoreDevkitBundle\Command\Helper;

use Symfony\Component\Console\Helper\FormatterHelper;
use Symfony\Component\Console\Helper\QuestionHelper as BaseQuestionHelper;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class QuestionHelper
 */
class QuestionHelper extends BaseQuestionHelper
{
    /**
     * @param InputInterface  $input
     * @param OutputInterface $output
     * @param array           $definitions
     *
     * @return void
     */
    public function writeParameterSummary(InputInterface $input, OutputInterface $output, array $definitions): void
    {
        if (empty($input->getOptions()) ||
            empty($definitions)) {
            return;
        }

        $this->writeSection($output, 'Parameter summary', 'bg=green;fg=white');

        foreach ($definitions as $definition) {
            if (!$definition instanceof InputOption) {
                continue;
            }

            /** @var mixed $value */
            $value = $input->getOption($definition->getName());
            $value = is_bool($value) ? ($value === true ? 'true' : 'false') : $value;

            if (is_array($value)) {
                $output->writeln(sprintf('%s: <info>%s</info>', $definition->getName(), json_encode($value)));
            } else {
                $output->writeln(sprintf('%s: <info>%s</info>', $definition->getName(), $value));
            }
        }
    }

    /**
     * @param OutputInterface $output
     * @param bool            $status
     *
     * @return void
     */
    public function writeGeneratorSummary(OutputInterface $output, bool $status = false): void
    {
        if ($status) {
            $this->writeSection($output, 'Everything is OK! Now get to work :).');

            return;
        }

        $this->writeSection($output,
            'The command was not able to configure everything automatically.' .
            'You\'ll need to make the following changes manually.',
            'error'
        );
    }

    /**
     * @param OutputInterface $output
     * @param string          $text
     * @param string          $style
     *
     * @return void
     */
    public function writeSection(OutputInterface $output, string $text, string $style = 'bg=blue;fg=white'): void
    {
        /** @var FormatterHelper $formatter */
        $formatter = $this->getHelperSet()->get('formatter');

        $output->writeln([
            '',
            $formatter->formatBlock($text, $style, true),
            '',
        ]);
    }

    /**
     * @param string      $question
     * @param string|null $default
     * @param string      $sep
     *
     * @return string
     */
    public function getQuestion(string $question, string $default = null, string $sep = ':'): string
    {
        return $default ? sprintf('<info>%s</info> [<comment>%s</comment>]%s ', $question, $default, $sep) : sprintf('<info>%s</info>%s ', $question, $sep);
    }
}
