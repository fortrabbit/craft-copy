<?php namespace fortrabbit\Copy\services;

trait ConsoleOutputHelper
{

    /**
     * @var \Symfony\Component\Console\Output\ConsoleOutputInterface
     */
    protected $output;

    /**
     * @var \Symfony\Component\Console\Style\SymfonyStyle
     */
    protected $style;

    /**
     * @param string $message
     */
    public function info($message, $newline = true)
    {
        $this->output->write("<info>$message</info>", $newline);
    }

    /**
     * @param string $message
     */
    public function error($message, $newline = true)
    {
        $this->output->write("<error>$message</error>", $newline);
    }

    /**
     * @param string $message
     */
    public function comment($message, $newline = true)
    {
        $this->output->write("<comment>$message</comment>", $newline);
    }

    /**
     * @param string $message
     */
    public function line($message)
    {
        $this->output->writeln("$message");
    }

    /**
     * @param string $message
     */
    public function write($message, $newline = false)
    {
        $this->output->write("$message", $newline);
    }

    /**
     * Formats a message as a block of text.
     *
     * @param string|array $messages The message to write in the block
     * @param string|null  $style    The style to apply to the whole block
     * @param string       $prefix   The prefix for the block
     * @param bool         $padding  Whether to add vertical padding
     * @param bool         $escape   Whether to escape the message
     */
    public function block($messages, $style = 'header', $padding = true, $escape = true)
    {
        $this->style->block($messages, null, $style, '  ', $padding, $escape);
    }
}
