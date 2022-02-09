<?php
/**
 * Copyright © Getnet. All rights reserved.
 *
 * @author    Bruno Elisei <brunoelisei@o2ti.com>
 * See LICENSE for license details.
 */

namespace Getnet\PaymentMagento\Model\Adminhtml\Source;

use Magento\Framework\Data\Form\Element\AbstractElement;

/**
 * Fraud Manager Block - Information to Fraud Manager.
 */
class FraudManager extends \Magento\Config\Block\System\Config\Form\Field
{
    /**
     * Render element value.
     *
     * @param AbstractElement $element
     *
     * @return string
     */
    public function render(AbstractElement $element)
    {
        $output = '<div class="getnet-featured-session">';
        $output .= '<h2 class="getnet-sub-title">'.__('Attention, a new cookie will be created.').'</h2>';
        $output .= '<div>'.__('Adjust your cookie information page.').'</div>';
        $output .= '<p>'.__('New cookie name: "thx_guid"').'</p>';
        $output .= '<p>'.__('Description of use: User identification cookie with Getnet payment method.').'</p>';
        $output .= '</div>';

        return '<div id="row_'.$element->getHtmlId().'">'.$output.'</div>';
    }
}
