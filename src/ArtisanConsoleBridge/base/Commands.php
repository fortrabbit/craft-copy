<?php

namespace fortrabbit\Copy\ArtisanConsoleBridge\base;

use fortrabbit\Copy\ArtisanConsoleBridge\ArtisanConsoleBehavior;
use Symfony\Component\Console\Question\ChoiceQuestion;
use Yii;
use yii\base\ActionEvent;
use yii\base\Event;
use yii\console\Controller as BaseConsoleController;
use yii\console\Controller;
use yii\console\ExitCode;
use yii\helpers\Inflector;


/**
 * Copy Craft effortlessly
 */
class Commands extends BaseConsoleController
{

    public $actions = [];

    public $defaultAction;

    public $options = [];

    public $optionAliases = [];

    /**
     * @return array
     */
    public function actions()
    {
        return $this->actions;
    }

    public function optionAliases()
    {
        return $this->optionAliases;
    }

    /**
     * @param string $prefix
     * @param array  $actions
     * @param null   $defaultAction
     */
    public static function registerCommands($prefix = '', $actions = [], $defaultAction = null)
    {
        Yii::$app->controllerMap[$prefix] = [
            'class'         => get_called_class(),
            'actions'       => $actions,
            'defaultAction' => $defaultAction
        ];

    }

    public static function registerOptions($prefix = '', $optionNames = [])
    {

        Yii::$app->controllerMap[$prefix]['optionAliases'] = $optionNames;

        Event::on(Controller::class, Controller::EVENT_BEFORE_ACTION, function (ActionEvent $event) use ($optionNames) {

            // Standalone Action
            $event->action->attachBehavior('artisan', ArtisanConsoleBehavior::class);

            foreach (array_values($optionNames) as $name) {
                if (in_array($name, array_values($optionNames))) {
                    if (isset($event->action->controller->options[$name])) {
                        $event->action->$name = $event->action->controller->options[$name];
                    }
                }
            }
        });

    }

    public function actionFoooo($hello = 'you')
    {

        $this->output->writeln("<error>FOOFOOFOO</error>");

        /** @var \Symfony\Component\Console\Helper\QuestionHelper $helper */
        $helper = $this->getHelper('question');


        $helper   = $this->getHelper('question');
        $question = new ChoiceQuestion(
            'Please select your favorite color (defaults to red)',
            array('red', 'blue', 'yellow'),
            0
        );
        $question->setErrorMessage('Color %s is invalid.');

        $color = $helper->ask($this->input, $this->output, $question);

        $this->output->writeln('You have just selected: ' . $color);


    }

    /**
     * @inheritdoc
     */
    public function options($actionID)
    {
        $actionClass   = $this->actions()[$actionID] ?? null;
        $action        = new \ReflectionClass($actionClass);
        $actionOptions = [];

        foreach ($action->getProperties(\ReflectionProperty::IS_PUBLIC) as $property) {
            if (!in_array($property->getName(), ['id', 'controller'])) {
                $actionOptions[] = $property->getName();
            }
        }

        return $actionOptions;

    }

    /**
     * Options getter
     *
     * @param string $name
     *
     * @return bool|mixed
     */
    public function __get($name)
    {
        return null;
    }

    /**
     * Options setter
     *
     * @param string $name
     * @param mixed  $value
     */
    public function __set($name, $value)
    {
        var_dump($name);
        $this->options[$name] = $value;
    }


    /**
     * Returns the help information for the options for the action.
     *
     * The returned value should be an array. The keys are the option names, and the values are
     * the corresponding help information. Each value must be an array of the following structure:
     *
     * - type: string, the PHP type of this argument.
     * - default: string, the default value of this argument
     * - comment: string, the comment of this argument
     *
     * The default implementation will return the help information extracted from the doc-comment of
     * the properties corresponding to the action options.
     *
     * @param Action $action
     *
     * @return array the help information of the action options
     */
    public function getActionOptionsHelp($action)
    {
        $optionNames = $this->options($action->id);
        if (empty($optionNames)) {
            return [];
        }

        $class   = new \ReflectionClass($action);
        $options = [];

        foreach ($class->getProperties() as $property) {
            $name = $property->getName();
            if (!in_array($name, $optionNames, true)) {
                continue;
            }
            $defaultValue = $property->getValue($action);
            $tags         = $this->parseDocCommentTags($property);

            // Display camelCase options in kebab-case
            $name = Inflector::camel2id($name, '-', true);

            if (isset($tags['var']) || isset($tags['property'])) {
                $doc = isset($tags['var']) ? $tags['var'] : $tags['property'];
                if (is_array($doc)) {
                    $doc = reset($doc);
                }
                if (preg_match('/^(\S+)(.*)/s', $doc, $matches)) {
                    $type    = $matches[1];
                    $comment = $matches[2];
                } else {
                    $type    = null;
                    $comment = $doc;
                }
                $options[$name] = [
                    'type'    => $type,
                    'default' => $defaultValue,
                    'comment' => $comment,
                ];
            } else {
                $options[$name] = [
                    'type'    => null,
                    'default' => $defaultValue,
                    'comment' => '',
                ];
            }
        }

        return $options;
    }

}
