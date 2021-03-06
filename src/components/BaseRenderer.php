<?php

/**
 * @link https://github.com/unclead/yii2-multiple-input
 * @copyright Copyright (c) 2014 unclead
 * @license https://github.com/unclead/yii2-multiple-input/blob/master/LICENSE.md
 */

namespace unclead\widgets\components;

use Yii;
use yii\helpers\Html;
use yii\base\InvalidConfigException;
use yii\base\Model;
use yii\base\NotSupportedException;
use yii\base\Object;
use yii\db\ActiveRecord;
use yii\helpers\Json;
use yii\web\View;
use unclead\widgets\MultipleInput;
use unclead\widgets\TabularInput;
use unclead\widgets\assets\MultipleInputAsset;

/**
 * Class BaseRenderer
 * @package unclead\widgets\components
 */
abstract class BaseRenderer extends Object
{
    /**
     * @var
     */
    public $id;

    /**
     * @var ActiveRecord[]|Model[]|array[] input data
     */
    public $data = null;

    /**
     * @var array
     */
    public $columns = [];

    /**
     * @var integer inputs limit
     */
    public $limit;

    /**
     * @var array client-side attribute options, e.g. enableAjaxValidation. You may use this property in case when
     * you use widget without a model, since in this case widget is not able to detect client-side options
     * automatically.
     */
    public $attributeOptions = [];

    /**
     * @var array the HTML options for the `remove` button
     */
    public $removeButtonOptions;

    /**
     * @var array the HTML options for the `add` button
     */
    public $addButtonOptions;

    /**
     * @var bool whether to allow the empty list
     */
    public $allowEmptyList = false;

    /**
     * @var string
     */
    public $columnClass;


    /**
     * @var TabularInput|MultipleInput
     */
    protected $context;

    /**
     * @param $context
     */
    public function setContext($context)
    {
        $this->context = $context;
    }

    public function init()
    {
        parent::init();

        if (empty($this->columnClass)) {
            throw new InvalidConfigException('You must specify "columnClass"');
        }

        if (!class_exists($this->columnClass)) {
            throw new InvalidConfigException('Column class "' . $this->columnClass. '" does not exist');
        }

        $this->prepareButtonsOptions();
    }

    private function prepareButtonsOptions()
    {
        if (!isset($this->removeButtonOptions['class'])) {
            $this->removeButtonOptions['class'] = 'btn btn-danger';
        }

        if (!isset($this->removeButtonOptions['label'])) {
            $this->removeButtonOptions['label'] = Html::tag('i', null, ['class' => 'glyphicon glyphicon-remove']);
        }

        if (!isset($this->addButtonOptions['class'])) {
            $this->addButtonOptions['class'] = 'btn btn-default';
        }

        if (!isset($this->addButtonOptions['label'])) {
            $this->addButtonOptions['label'] = Html::tag('i', null, ['class' => 'glyphicon glyphicon-plus']);
        }
    }


    /**
     * Creates column objects and initializes them.
     */
    protected function initColumns()
    {
        foreach ($this->columns as $i => $column) {
            $definition = array_merge([
                'class' => $this->columnClass,
                'renderer' => $this
            ], $column);

            if ($this->context instanceof MultipleInput) {
                $definition['widget'] = $this->context;
            }
            $column = Yii::createObject($definition);
            $this->columns[$i] = $column;
        }
    }

    public function render()
    {
        $this->initColumns();
        $content = $this->internalRender();
        $this->registerClientScript();
        return $content;
    }

    /**
     * @return mixed
     * @throws NotSupportedException
     */
    abstract protected function internalRender();

    /**
     * Register script.
     *
     */
    protected function registerClientScript()
    {
        $view = $this->context->getView();
        MultipleInputAsset::register($view);

        $jsBefore = $this->collectJsTemplates();
        $template = $this->prepareTemplate();
        $jsTemplates = $this->collectJsTemplates($jsBefore);

        $options = Json::encode(
            [
                'id'                => $this->id,
                'template'          => $template,
                'jsTemplates'       => $jsTemplates,
                'limit'             => $this->limit,
                'attributeOptions'  => $this->attributeOptions,
            ]
        );

        $js = "jQuery('#{$this->id}').multipleInput($options);";
        $view->registerJs($js);
    }

    /**
     * @return string
     */
    abstract protected function prepareTemplate();


    protected function collectJsTemplates($except = [])
    {
        $view = $this->context->getView();
        $output = [];
        if (is_array($view->js) && array_key_exists(View::POS_READY, $view->js)) {
            foreach ($view->js[View::POS_READY] as $key => $js) {
                if (array_key_exists($key, $except)) {
                    continue;
                }
                if (preg_match('/^[^{]+{multiple-index}.*$/m', $js) === 1) {
                    $output[$key] = $js;
                    unset($view->js[View::POS_READY][$key]);
                }
            }
        }
        return $output;
    }
}