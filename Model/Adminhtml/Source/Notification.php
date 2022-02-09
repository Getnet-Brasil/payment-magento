<?php
/**
 * Copyright © Getnet. All rights reserved.
 *
 * @author    Bruno Elisei <brunoelisei@o2ti.com>
 * See LICENSE for license details.
 */

namespace Getnet\PaymentMagento\Model\Adminhtml\Source;

use Getnet\PaymentMagento\Gateway\Config\Config;
use Magento\Framework\Data\Form\Element\AbstractElement;

/**
 * Notification Block - Information to Notification.
 */
class Notification extends \Magento\Config\Block\System\Config\Form\Field
{
    /**
     * @var Config
     */
    protected $config;

    /**
     * @param Config $config
     */
    public function __construct(
        Config $config
    ) {
        $this->config = $config;
    }

    /**
     * Render element value.
     *
     * @param AbstractElement $element
     *
     * @return string
     */
    public function render(AbstractElement $element)
    {
        $sellerId = $this->config->getMerchantGatewaySellerId();
        $output = '<div class="getnet-featured-session">';
        $output .= '<h2 class="getnet-sub-title">'.__('Configure credentials first').'</h2>';
        $output .= '</div>';
        if ($sellerId) {
            $output = '<div class="getnet-featured-session">';
            $output .= '<h2 class="getnet-sub-title">'.__('Payment callback PIX').'</h2>';
            // phpcs:ignore Generic.Files.LineLength
            $output .= '<div>'.__('Enter your store url followed by "getnet/notification/pix/seller_id/%1/', $sellerId).'</div>';
            // phpcs:ignore Generic.Files.LineLength
            $output .= '<p>'.__('Example: https://yourstoreurl.com/getnet/notification/pix/seller_id/%1/', $sellerId).'</p>';
            $output .= '<h2 class="getnet-sub-title">'.__('Callback from boleto').'</h2>';
            // phpcs:ignore Generic.Files.LineLength
            $output .= '<div>'.__('Enter your store url followed by "getnet/notification/boleto/seller_id/%1/', $sellerId).'"</div>';
            // phpcs:ignore Generic.Files.LineLength
            $output .= '<p>'.__('Example: https://yourstoreurl.com/getnet/notification/boleto/seller_id/%1/', $sellerId).'</p>';
            $output .= '</div>';
        }

        return '<div id="row_'.$element->getHtmlId().'">'.$output.'</div>';
    }
}
