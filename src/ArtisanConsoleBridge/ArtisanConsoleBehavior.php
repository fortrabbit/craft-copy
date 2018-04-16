<?php
/**
 * Created by PhpStorm.
 * User: os
 * Date: 12.04.18
 * Time: 09:57
 */

namespace fortrabbit\Copy\ArtisanConsoleBridge;


use Symfony\Component\Console\Helper\DebugFormatterHelper;
use Symfony\Component\Console\Helper\FormatterHelper;
use Symfony\Component\Console\Helper\HelperSet;
use Symfony\Component\Console\Helper\ProcessHelper;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\ArgvInput;
use yii\base\Behavior;

/**
 * Class ArtisanConsoleBehavior
 *
 * @package fortrabbit\Copy\ArtisanConsoleBridge
 */
class ArtisanConsoleBehavior extends Behavior
{
    /**
     * @var \Symfony\Component\Console\Helper\HelperSet
     */
    protected $helperSet;

    /**
     * @var \Symfony\Component\Console\Output\OutputInterface
     */
    public $output;

    /**
     * @var \Symfony\Component\Console\Input\InputInterface
     */
    public $input;

    public function init()
    {
        parent::init();

        $this->input = new ArgvInput();
        $this->output = new OutputStyle($this->input, new ConsoleOutput());

        $this->helperSet = new HelperSet([
            new FormatterHelper(),
            new DebugFormatterHelper(),
            new ProcessHelper(),
            new QuestionHelper(),
        ]);
    }

    /**
     * Gets a helper instance by name.
     *
     * @param string $name The helper name
     *
     * @return mixed The helper value
     *
     * @throws \InvalidArgumentException if the helper is not defined
     *
     */
    public function getHelper($name)
    {
        return $this->helperSet->get($name);
    }
}
