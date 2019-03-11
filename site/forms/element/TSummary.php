<?php

namespace Thrust\Forms\Element;

use Phalcon\Forms\Element;

class TSummary extends Element
{
    /**
     * TSummary constructor.
     * @param string $name
     * @param null $attributes
     */
    public function __construct($name, $attributes = null)
    {
		parent::__construct($name, $attributes);
    }

    /**
     * render TSummary html
     * @param  array $attributes
     * @return mixed
     */
    public function render($attributes = null)
    {
       // $html = '<pre>' . print_r($attributes, true) . '</pre>';

        $html = '<div class="summary_container">';

        $html .= '<p class="summary_header">Your Information</p>';

        $html .= '<p>';
            // $html .= $attributes['saved_data']['todyl_first_name'] . ' ' . $attributes['saved_data']['todyl_last_name'] . '<br />';
            $html .= $attributes['saved_data']['todyl_email'] . '<br />';
            // $html .= $attributes['saved_data']['todyl_business_name'];
        $html .= '</p>';

        $html .= '<p class="summary_header">Your Todyl Protection Summary</p>';

        $html .= '<ul>';
            $html .= '<li>Todyl Defender for ' . $attributes['saved_data']['num_devices'] . ' ' . ($attributes['saved_data']['num_devices'] == 1 ? 'device' : 'devices') . '</li>';
            $html .= '<li>Guardian Cloud 24/7 monitoring</li>';
            // $html .= '<li>' . ucfirst($attributes['saved_data']['package']) . ' Cyber Response</li>';
            $html .= '<li>Todyl Cyber Support</li>';
        $html .= '</ul>';

        $html .= '<div style="clear: both; border-bottom: 1px solid #8a8a8a; margin-bottom: 10px"></div>';

        $html .= '<div id="subtotal_container"></div>';

        $html .= '<p class="pt-1">You will not be billed until your 15 day trial is over. You can cancel anytime.</p>';

        $html .= '</div>';

        return $html;
    }
}
