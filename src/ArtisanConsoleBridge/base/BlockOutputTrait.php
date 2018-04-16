<?php

namespace fortrabbit\Copy\ArtisanConsoleBridge\base;

/**
 * Trait BlockOutputTrait
 *
 *
 */
trait BlockOutputTrait
{
    /**
     * @var \fortrabbit\Copy\ArtisanConsoleBridge\OutputStyle
     */
    public $output;

    /**
     * @var \Symfony\Component\Console\Input\InputInterface
     */
    public $input;

    /**
     * Formats a message as a block of text.
     *
     * @param string|array $messages The message to write in the block
     * @param string|null  $type     The block type (added in [] on first line)
     * @param string|null  $style    The style to apply to the whole block
     * @param string       $prefix   The prefix for the block
     * @param bool         $padding  Whether to add vertical padding
     * @param bool         $escape   Whether to escape the message
     */
    public function block($messages, $type = null, $style = null, $prefix = ' ', $padding = true, $escape = true)
    {
        $this->output->block($messages, $type, $style, $prefix, $padding, $escape);
    }

    /**
     * @param $message
     */
    public function title($message) {
        $this->output->title($message);
    }

    /**
     * @param $message
     */
    public function section($message)
    {
        $this->output->section($message);
    }

    /**
     * @param array $elements
     */
    public function listing(array $elements) {
        $this->output->listing($elements);
    }

    /**
     * Formats a command comment.
     *
     * @param string|array $message
     */
    public function commentBlock($message)
    {
        $this->block($message, null, null, '<fg=default;bg=default> // </>', false, false);
    }

    /**
     * {@inheritdoc}
     */
    public function successBlock($message)
    {
        $this->block($message, 'OK', 'fg=black;bg=green', ' ', true);
    }

    /**
     * {@inheritdoc}
     */
    public function errorBlock($message)
    {
        $this->block($message, 'ERROR', 'fg=white;bg=red', ' ', true);
    }

    /**
     * {@inheritdoc}
     */
    public function warningBlock($message)
    {
        $this->block($message, 'WARNING', 'fg=white;bg=red', ' ', true);
    }

    /**
     * {@inheritdoc}
     */
    public function noteBlock($message)
    {
        $this->block($message, 'NOTE', 'fg=yellow', ' ░ ');
    }

    /**
     * {@inheritdoc}
     */
    public function cautionBlock($message)
    {
        $this->block($message, 'CAUTION', 'fg=white;bg=red', '» ', true);
    }
}
