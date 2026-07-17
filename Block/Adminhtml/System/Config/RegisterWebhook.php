<?php
/**
 * Copyright © Getnet. All rights reserved.
 *
 * @author    Bruno Elisei <brunoelisei@o2ti.com>
 * See LICENSE for license details.
 */

declare(strict_types=1);

namespace Getnet\PaymentMagento\Block\Adminhtml\System\Config;

use Magento\Backend\Block\Widget\Button;
use Magento\Config\Block\System\Config\Form\Field;
use Magento\Framework\Data\Form\Element\AbstractElement;

/**
 * Class RegisterWebhook - Button to register Global API webhook subscriptions.
 */
class RegisterWebhook extends Field
{
    /**
     * @var string
     */
    protected $_template = 'Getnet_PaymentMagento::system/config/register-webhook.phtml';

    /**
     * Render - remove scope decorations.
     *
     * @param AbstractElement $element
     *
     * @return string
     */
    public function render(AbstractElement $element)
    {
        $element->unsScope()->unsCanUseWebsiteValue()->unsCanUseDefaultValue();

        return parent::render($element);
    }

    /**
     * Get Element Html - renders the button template.
     *
     * @param AbstractElement $element
     *
     * @return string
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    protected function _getElementHtml(AbstractElement $element)
    {
        return $this->_toHtml();
    }

    /**
     * Get Ajax Url of the register controller.
     *
     * @return string
     */
    public function getAjaxUrl(): string
    {
        return $this->getUrl('getnet/webhook/register', [
            'store' => (int) $this->getRequest()->getParam('store', 0),
        ]);
    }

    /**
     * Get Button Html.
     *
     * @return string
     */
    public function getButtonHtml(): string
    {
        $button = $this->getLayout()->createBlock(Button::class)->setData([
            'id'    => 'getnet_register_webhook',
            'label' => __('Register Webhooks'),
        ]);

        return $button->toHtml();
    }
}
