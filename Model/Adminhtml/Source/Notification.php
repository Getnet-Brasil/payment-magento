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
use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\Url;

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
     * @var StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @var Url
     */
    protected $helperUrl;

    /**
     * @param Config                $config
     * @param StoreManagerInterface $storeManager
     * @param Url                   $helperUrl
     */
    public function __construct(
        Config $config,
        StoreManagerInterface $storeManager,
        Url $helperUrl
    ) {
        $this->config = $config;
        $this->storeManager = $storeManager;
        $this->helperUrl = $helperUrl;
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
        $storeId = $this->storeManager->getDefaultStoreView()->getStoreId();
        $sellerId = $this->config->getMerchantGatewaySellerId();
        $output = '<div class="getnet-featured-session">';
        $output .= '<h2 class="getnet-sub-title">'.__('Configure credentials first').'</h2>';
        $output .= '</div>';
        if ($sellerId) {
            $param = ['seller_id' => $sellerId];
            $webhookUrl =  $this->helperUrl->getUrl('getnet/notification/all', $param);

            $output = '<div class="getnet-featured-session">';

            $output .= '<h2 class="getnet-sub-title">'.__('Register the Callback URL').'</h2>';
            $output .= '<div>'.__('1st - Access your Getnet account dashboard.').'</div>';
            $output .= '<div>'.__('2nd - Look for Products and Services.').'</div>';
            $output .= '<div>'.
                __('3rd - In any Callback request, paste the URL provided: <strong>%1</strong>', $webhookUrl)
            .'</div>';

            $output .= '</div>';

            $output .= '<div id="messages"><div class="messages"><div class="message message-warning warning"><div>';
            $output .= '<h2 class="getnet-sub-title">'
                .__('After registering your URL, you need to request approval in Getnet firewall.')
            .'</h2>';

            $output .= '<p>'
                .__('To do this, send an email to %1.', 'suporte.edigital@getnet.com.br')
            .'</p>';
            $output .= '<p>'
                .__('Informing the approval of the URL you registered in the dashboard.')
            .'</p>';
            // phpcs:ignore Generic.Files.LineLength
            $output .= '</div></div></div></div>';
        }

        return '<div id="row_'.$element->getHtmlId().'">'.$output.'</div>';
    }
}
