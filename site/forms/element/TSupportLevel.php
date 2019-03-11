<?php

namespace Thrust\Forms\Element;

use Phalcon\Forms\Element;

class TSupportLevel extends Element
{
    protected $_options;

    /**
     * TSupportLevel constructor.
     * @param string $name
     * @param array $attributes
     */
    public function __construct($name, $options = null, $attributes = null)
    {
        $this->_options = $options;
		parent::__construct($name, $attributes);
    }

    /**
     * render TSupportLevel html
     * @param  array $attributes
     * @return mixed
     */
    public function render($attributes = null)
    {
        $html = '';
        // $html .= '<pre>' . print_r($this->_options, true) . '</pre>';

        $current_val = isset($attributes['value']) ? $attributes['value'] : array_values($this->_options)[0]['value'];

        foreach ($this->_options as $key => $support_level) {
            $option_id = str_replace(' ', '_', $this->getName() . '-' . $key);

            $checked = '';
            if ($current_val == $support_level['value']) {
                $checked = ' checked';
            }

            $html .= '<div class="ts_option">';
            $html .= '<input type="radio" id="' . $option_id .'" name="' . $this->getName() . '" value="' . $support_level['value'] . '"' . $checked . ' />';
            $html .= '<label for="' . $option_id .'" class="radio-label">' . $support_level['label'] . '<span class="sub-label">' . $support_level['price'] . '</span></label>';

            $html .= '<div class="radio-description">' . $support_level['description'] . '</div>';

            $html .= '</div>';
        }

        return $html;
    }
}
