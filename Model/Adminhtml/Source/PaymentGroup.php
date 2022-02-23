<?php
/**
 * Copyright © Getnet. All rights reserved.
 *
 * @author    Bruno Elisei <brunoelisei@o2ti.com>
 * See LICENSE for license details.
 */

namespace Getnet\PaymentMagento\Model\Adminhtml\Source;

/**
 * Class PaymentGroup - Fieldset renderer for getnet.
 */
class PaymentGroup extends \Magento\Config\Block\System\Config\Form\Fieldset
{
    /**
     * Return header comment part of html for fieldset.
     *
     * @param \Magento\Framework\Data\Form\Element\AbstractElement $element
     *
     * @return string
     */
    protected function _getHeaderCommentHtml($element)
    {
        $groupConfig = $element->getGroup();

        if (empty($groupConfig['help_url']) || !$element->getComment()) {
            return parent::_getHeaderCommentHtml($element);
        }

        $html = '<div class="comment">'.
            $element->getComment().
            ' <a target="_blank" href="'.
            $groupConfig['help_url'].
            '">'.
            __(
                'Help'
            ).'</a></div>';

        return $html;
    }

    /**
     * Return collapse state.
     *
     * @param \Magento\Framework\Data\Form\Element\AbstractElement $element
     *
     * @return bool
     */
    protected function _isCollapseState($element)
    {
        $extra = $this->_authSession->getUser()->getExtra();
        if (isset($extra['configState'][$element->getId()])) {
            return $extra['configState'][$element->getId()];
        }

        $groupConfig = $element->getGroup();
        if (!empty($groupConfig['expanded'])) {
            return true;
        }

        return false;
    }
}
