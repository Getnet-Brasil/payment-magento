<?php
/**
 * Copyright © Getnet. All rights reserved.
 *
 * @author    Bruno Elisei <brunoelisei@o2ti.com>
 * See LICENSE for license details.
 */

declare(strict_types=1);

namespace Getnet\PaymentMagento\Block\Adminhtml\System\Config;

use Getnet\PaymentMagento\Model\OpenFinanceManagement;
use Magento\Backend\Block\Template\Context;
use Magento\Backend\Block\Widget\Button;
use Magento\Config\Block\System\Config\Form\Field;
use Magento\Framework\Data\Form\Element\AbstractElement;

/**
 * Button that registers the Pix by biometrics return URL (Open Finance merchant domain).
 */
class RegisterOpenFinanceDomain extends Field
{
    /**
     * @var string
     */
    protected $_template = 'Getnet_PaymentMagento::system/config/register-openfinance-domain.phtml';

    /**
     * @var OpenFinanceManagement
     */
    protected $openFinanceManagement;

    /**
     * @param Context               $context
     * @param OpenFinanceManagement $openFinanceManagement
     * @param array                 $data
     */
    public function __construct(
        Context $context,
        OpenFinanceManagement $openFinanceManagement,
        array $data = []
    ) {
        $this->openFinanceManagement = $openFinanceManagement;
        parent::__construct($context, $data);
    }

    /**
     * Render without scope/default checkboxes.
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
     * Element html.
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
     * Store id of the current config scope.
     *
     * @return int
     */
    public function getStoreId(): int
    {
        return (int) $this->getRequest()->getParam('store', 0);
    }

    /**
     * Ajax url.
     *
     * @return string
     */
    public function getAjaxUrl(): string
    {
        return $this->getUrl('getnet/pixbiometric/registerDomain', [
            'store' => $this->getStoreId(),
        ]);
    }

    /**
     * Return url that will be registered.
     *
     * @return string
     */
    public function getReturnUrl(): string
    {
        try {
            return $this->openFinanceManagement->getReturnUrl($this->getStoreId() ?: null);
        } catch (\Exception $exc) {
            return '';
        }
    }

    /**
     * Button html.
     *
     * @return string
     */
    public function getButtonHtml(): string
    {
        $button = $this->getLayout()->createBlock(Button::class)->setData([
            'id'    => 'getnet_register_openfinance_domain',
            'label' => __('Register Return URL'),
        ]);

        return $button->toHtml();
    }
}
