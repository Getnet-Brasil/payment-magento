<?php
/**
 * Copyright © Getnet. All rights reserved.
 *
 * @author    Bruno Elisei <brunoelisei@o2ti.com>
 * See LICENSE for license details.
 */

namespace Getnet\PaymentMagento\Gateway\Request;

use InvalidArgumentException;
use Magento\Payment\Gateway\Data\PaymentDataObjectInterface;
use Magento\Payment\Gateway\Request\BuilderInterface;

/**
 * Class Data for Two Cc - Payment Id structure.
 */
class DataForTwoCcRequest implements BuilderInterface
{
    /**
     * Getnet Payment Id block name.
     */
    public const GETNET_PAYMENT_ID = 'payment_id';

    /**
     * Store Id block name.
     */
    public const PAYMENT_TAG = 'payment_tag';

    /**
     * Cc Payment Id Secondary - Payment Addtional Information.
     */
    public const PAYMENT_INFO_PAYMENT_ID_SECONDARY = 'payment_id_secondary';

    /**
     * Cc Payment Id - Payment Addtional Information.
     */
    public const PAYMENT_INFO_PAYMENT_ID = 'payment_id';

    /**
     * Build.
     *
     * @param array $buildSubject
     */
    public function build(array $buildSubject)
    {
        $result = [];
        if (!isset($buildSubject['payment'])
        || !$buildSubject['payment'] instanceof PaymentDataObjectInterface
        ) {
            throw new InvalidArgumentException('Payment data object should be provided');
        }

        $paymentDO = $buildSubject['payment'];

        $payment = $paymentDO->getPayment();

        $firstPayId = $payment->getAdditionalInformation(self::PAYMENT_INFO_PAYMENT_ID);
        $secondPayId = $payment->getAdditionalInformation(self::PAYMENT_INFO_PAYMENT_ID_SECONDARY);
        $order = $paymentDO->getOrder();
        $orderId = $order->getOrderIncrementId();
        $firstPayTag = $orderId.'-1';
        $secondPayTag = $orderId.'-2';

        $result['payments'][] = [
            self::GETNET_PAYMENT_ID => $firstPayId,
            self::PAYMENT_TAG       => $firstPayTag,
        ];

        $result['payments'][] = [
            self::GETNET_PAYMENT_ID => $secondPayId,
            self::PAYMENT_TAG       => $secondPayTag,
        ];

        return $result;
    }
}
