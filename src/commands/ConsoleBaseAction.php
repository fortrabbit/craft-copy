<?php namespace fortrabbit\Copy\commands;


use craft\errors\ActionCancelledException;
use fortrabbit\Copy\services\ConsoleOutputHelper;
use Symfony\Component\Console\Output\ConsoleOutput;
use yii\base\Action;

abstract class ConsoleBaseAction extends Action
{
    const OPTIONS = [];

    protected $success = true;

    protected $args = [];

    protected $options = [];

    /**
     * @var \Symfony\Component\Console\Output\ConsoleOutputInterface
     */
    protected $output;

    use ConsoleOutputHelper;

    public function __construct($id, \yii\base\Controller $controller, array $config = [])
    {
        $this->output = $this->output = new ConsoleOutput();

        parent::__construct($id, $controller, $config);
    }


    public function getOption($name, $defaultValue = null)
    {
        return $this->controller->$name ?? $defaultValue;
    }

    /**
     * @param $question
     *
     * @return bool
     * @throws \craft\errors\ActionCancelledException
     */
    public function isForcedOrConfirmed($question)
    {
        if ($this->getOption('force')) {
            return true;
        }
        if ($this->controller->confirm(PHP_EOL . $question, true)) {
            return true;
        }

        throw new ActionCancelledException("Cancelled. Action was not executed.");
    }
}

