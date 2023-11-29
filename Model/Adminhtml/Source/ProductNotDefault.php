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
 * Notification Block - Information to Notification.
 */
class ProductNotDefault extends \Magento\Config\Block\System\Config\Form\Field
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
        $output = '<div id="messages"><div class="messages"><div class="message message-warning warning"><div>';
        $output .= '<p class="getnet-sub-title">'.__('This product is not enabled by default.').'</p>';
        $output .= '<p>'.__('To use it, <b>prior contracting is necessary</b>.')
        .__('Please get in touch with your Getnet representative.').'</p>';
        $output .= '</div></div></div></div>';

        return '<div id="row_'.$element->getHtmlId().'">'.$output.'</div>';
    }
}
