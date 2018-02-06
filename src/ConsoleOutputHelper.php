<?php namespace fortrabbit\Sync;

trait ConsoleOutputHelper
{

    /**
     * @var \Symfony\Component\Console\Output\ConsoleOutputInterface
     */
    protected $output;

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
}
