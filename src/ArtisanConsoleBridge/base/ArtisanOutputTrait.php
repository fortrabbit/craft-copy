<?php

namespace fortrabbit\Copy\ArtisanConsoleBridge\base;


use fortrabbit\Copy\ArtisanConsoleBridge\ConsoleOutput;
use fortrabbit\Copy\ArtisanConsoleBridge\OutputStyle;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\ArgvInput;
use Symfony\Component\Console\Question\ChoiceQuestion;
use Symfony\Component\Console\Question\Question;
use yii\base\Arrayable;

/**
 * Trait ArtisanTrait
 */
trait ArtisanOutputTrait
{

    /**
     * @var OutputStyle
     */
    public $output;

    /**
     * @var \Symfony\Component\Console\Input\InputInterface
     */
    public $input;


    public function init()
    {
        parent::init();

        $this->input  = new ArgvInput();
        $this->output = new OutputStyle($this->input, new ConsoleOutput());

    }

    /**
     * Prompt the user for input.
     *
     * @param  string      $question
     * @param  string|null $default
     *
     * @return string
     */
    public function ask($question, $default = null)
    {
        return $this->output->ask($question, $default);
    }

    /**
     * Prompt the user for input with auto completion.
     *
     * @param  string      $question
     * @param  array       $choices
     * @param  string|null $default
     *
     * @return string
     */
    public function anticipate($question, array $choices, $default = null)
    {
        return $this->askWithCompletion($question, $choices, $default);
    }

    /**
     * Prompt the user for input with auto completion.
     *
     * @param  string      $question
     * @param  array       $choices
     * @param  string|null $default
     *
     * @return string
     */
    public function askWithCompletion($question, array $choices, $default = null)
    {
        $question = new Question($question, $default);
        $question->setAutocompleterValues($choices);

        return $this->output->askQuestion($question);
    }

    /**
     * Prompt the user for input but hide the answer from the console.
     *
     * @param  string $question
     * @param  bool   $fallback
     *
     * @return string
     */
    public function secret($question, $fallback = true)
    {
        $question = new Question($question);
        $question->setHidden(true)->setHiddenFallback($fallback);

        return $this->output->askQuestion($question);
    }

    /**
     * Give the user a single choice from an array of answers.
     *
     * @param  string      $question
     * @param  array       $choices
     * @param  string|null $default
     * @param  mixed|null  $attempts
     * @param  bool|null   $multiple
     *
     * @return string
     */
    public function choice($question, array $choices, $default = null, $attempts = null, $multiple = null)
    {
        $question = new ChoiceQuestion($question, $choices, $default);
        $question->setMaxAttempts($attempts)->setMultiselect($multiple);

        return $this->output->askQuestion($question);
    }

    /**
     * Format input to textual table.
     *
     * @param        $headers
     * @param        $rows
     * @param string $tableStyle
     * @param array  $columnStyles
     */
    public function table($headers, $rows, $tableStyle = 'default', array $columnStyles = [])
    {
        $table = new Table($this->output);
        if ($rows instanceof Arrayable) {
            $rows = $rows->toArray();
        }
        $table->setHeaders((array)$headers)->setRows($rows)->setStyle($tableStyle);
        foreach ($columnStyles as $columnIndex => $columnStyle) {
            $table->setColumnStyle($columnIndex, $columnStyle);
        }
        $table->render();
    }

    /**
     * Write a string as information output.
     *
     * @param  string          $string
     * @param  null|int|string $verbosity
     *
     * @return void
     */
    public function info($string, $verbosity = null)
    {
        $this->line($string, 'info', $verbosity);
    }

    /**
     * Write a string as standard output.
     *
     * @param  string          $string
     * @param  string          $style
     * @param  null|int|string $verbosity
     *
     * @return void
     */
    public function line($string, $style = null, $verbosity = null)
    {
        $styled = $style ? "<$style>$string</$style>" : $string;
        $this->output->writeln($styled, $this->parseVerbosity($verbosity));
    }

    /**
     * Write a string as comment output.
     *
     * @param  string          $string
     * @param  null|int|string $verbosity
     *
     * @return void
     */
    public function comment($string, $verbosity = null)
    {
        $this->line($string, 'comment', $verbosity);
    }

    /**
     * Write a string as question output.
     *
     * @param  string          $string
     * @param  null|int|string $verbosity
     *
     * @return void
     */
    public function question($string, $verbosity = null)
    {
        $this->line($string, 'question', $verbosity);
    }

    /**
     * Write a string as error output.
     *
     * @param  string          $string
     * @param  null|int|string $verbosity
     *
     * @return void
     */
    public function error($string, $verbosity = null)
    {
        $this->line($string, 'error', $verbosity);
    }

    /**
     * Write a string as warning output.
     *
     * @param  string          $string
     * @param  null|int|string $verbosity
     *
     * @return void
     */
    public function warn($string, $verbosity = null)
    {
        if (!$this->output->getFormatter()->hasStyle('warning')) {
            $style = new OutputFormatterStyle('yellow');
            $this->output->getFormatter()->setStyle('warning', $style);
        }
        $this->line($string, 'warning', $verbosity);
    }

    /**
     * Write a string in an alert box.
     *
     * @param  string $string
     *
     * @return void
     */
    public function alert($string)
    {
        $this->comment(str_repeat('*', strlen($string) + 12));
        $this->comment('*     ' . $string . '     *');
        $this->comment(str_repeat('*', strlen($string) + 12));
        $this->output->newLine();
    }

    /**
     * Set the verbosity level.
     *
     * @param  string|int $level
     *
     * @return void
     */
    protected function setVerbosity($level)
    {
        $this->verbosity = $this->parseVerbosity($level);
    }

    /**
     * Get the verbosity level in terms of Symfony's OutputInterface level.
     *
     * @param  string|int|null $level
     *
     * @return int
     */
    protected function parseVerbosity($level = null)
    {
        if (isset($this->verbosityMap[$level])) {
            //$level = $this->verbosityMap[$level];
        } elseif (!is_int($level)) {
            //$level = $this->verbosity;
        }

        return $level;
    }

    /**
     * Get the console command arguments.
     *
     * @return array
     */
    protected function getArguments()
    {
        return [];
    }

    /**
     * Get the console command options.
     *
     * @return array
     */
    protected function getOptions()
    {
        return [];
    }

    /**
     * Get the output implementation.
     *
     * @return \Symfony\Component\Console\Output\OutputInterface
     */
    public function getOutput()
    {
        return $this->output;
    }
}
