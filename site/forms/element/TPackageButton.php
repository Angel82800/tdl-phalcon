<?php

namespace Thrust\Forms\Element;

use Phalcon\Forms\Element;

class TPackageButton extends Element
{
    /**
     * TPackageButton constructor.
     * @param string $name
     * @param null $attributes
     */
    public function __construct($name, $attributes = null)
    {
		parent::__construct($name, $attributes);
    }

    /**
     * render TPackageGroup html
     * @param  array $attributes
     * @return mixed
     */
    public function render($attributes = null)
    {
//        $html .= '<pre>' . print_r($attributes, true) . '</pre>';

        $html = '<div class="medium-6 small-6 columns">';
        $html .= '<p class="package_price" data-package="' . $attributes['package'] . '"></p>';
        $html .= '</div>';

        $html .= '<div class="medium-6 small-6 columns">';

        if (isset($attributes['value']) && $attributes['value'] == $attributes['package']) {
            // selected
            $html .= '<div class="package_select_btn selected">Selected!</div>';
            $html .= '<input type="radio" name="package" value="' . $attributes['package'] . '" checked />';
        } else {
            // normal
            $html .= '<div class="package_select_btn">Select</div>';
            $html .= '<input type="radio" name="package" value="' . $attributes['package'] . '" />';
        }

        $html .= '</div>';

        return $html;
    }
}
