<?php

namespace Thrust\Forms\Element;

use Phalcon\Forms\Element;

class TCheckboxGroup extends Element
{
    protected $_options;

    /**
     * TCheckboxGroup constructor.
     * @param string $name
     * @param array $options
     * @param array $attributes
     */
    public function __construct($name, $options = null, $attributes = null)
    {
        $this->_options = $options;
		parent::__construct($name, $attributes);
    }

    /**
     * render TCheckboxGroup html
     * @param  array $attributes
     * @return mixed
     */
    public function render($attributes = null)
    {
        $html = '';
        // $html .= '<pre>' . print_r($attributes, true) . '</pre>';

        foreach ($this->_options as $billingoption) {
            $option_id = str_replace(' ', '_', $this->getName() . '-' . $billingoption['value']);

            $checked = '';
            if (isset($attributes['value']) && $attributes['value'] == $billingoption['value']) {
                $checked = ' checked';
                $html .= '<div class="tc_option active">';
            } else {
                $html .= '<div class="tc_option">';
            }

            $html .= '<label for="' . $option_id .'">';
            if (isset($attributes['label'])) {
                $html .= $attributes['label'];
            } else {
                $html .= $billingoption['label'];
            }

            $html .= '<input type="' . $attributes['type'] . '" id="' . $option_id .'" name="' . $this->getName() . '" value="' . $billingoption['value'] . '"' . $checked . ' />';
            $html .= '<div class="option_indicator"></div>';
            $html .= '</label>';

            $html .= '</div>';
        }

        return $html;
    }
}
