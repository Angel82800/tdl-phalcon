<?php

namespace Thrust\Forms\Element;

use Phalcon\Forms\Element;

class TPackageGroup extends Element
{
    protected $_packages;

    /**
     * TPackageGroup constructor.
     * @param string $name
     * @param null $packages
     * @param null $attributes
     */
    public function __construct($name, $packages = null, $attributes = null)
    {
        $this->_packages = $packages;
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

        $packageHtml = '';
        $total = [
            'org_total' => 0,
            'total'     => 0,
        ];
        foreach ($this->_packages as $package) {
            $packageHtml .= '<div data-tooltip class="row package_option" title="' . $package['description'] . '">';
            $packageHtml .= '<div class="medium-6 small-6 columns">';

            if (! isset($attributes['text_only']) || ! $attributes['text_only']) {
                $packageHtml .= '<img src="' . $package['icon'] . '" />';
            }

            $packageHtml .= '<span>' . $package['title'] . '</span>';
            $packageHtml .= '</div>';

            $packageHtml .= '<div class="medium-6 small-6 columns r_align">';

            if ($package['org_price'] == $package['price'] || $package['price'] == 0) {
                // no discount
                $priceHtml = '$' . $package['org_price'];
            } else {
                // discount
                $priceHtml = '<span class="discount">$' . $package['org_price'] . '</span> $' . $package['price'];
            }
            $priceHtml .= '<span class="sub_text"> Per Month</span>';

            if (isset($attributes['is_beta']) && $attributes['is_beta'] == 'yes') {
                $priceHtml = '<strike>' . $priceHtml . '</strike>';
            }

            $packageHtml .= $priceHtml . '</div>';
            $packageHtml .= '</div>';

            $total['org_total'] += $package['org_price'];
            $total['total'] += $package['price'];
        }

        if ($total['org_total'] == $total['total'] || $total['total'] == 0) {
            // no discount
            $totalHtml = '$' . $total['org_total'];
        } else {
            // discount
            $totalHtml = '<span class="discount">$' . $total['org_total'] . '</span> $' . $total['total'];
        }
        $totalHtml .= '<span class="sub_text"> Per Month</span>';

        if (isset($attributes['is_beta']) && $attributes['is_beta'] == 'yes') {
            // beta client mode - discount!
            $totalHtml = '<div class="medium-12 small-12 columns r_align">Total: <strike>' . $totalHtml . '</strike></div>';
            $totalHtml .= '<div class="medium-12 small-12 columns r_align">Beta Client Total: $19.99 <span class="sub_text">Per Month</span></div>';
        } else {
            $totalHtml = '<div class="medium-12 small-12 columns r_align">Total: ' . $totalHtml . '</div>';
        }
        $totalHtml = '<div class="row total">' . $totalHtml . '</div>';

        $html = '<div class="packages_container">' . $packageHtml . $totalHtml . '</div>';

        return $html;
    }
}
