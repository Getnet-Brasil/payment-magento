<?php
/**
 * Copyright © Getnet. All rights reserved.
 *
 * @author    Bruno Elisei <brunoelisei@o2ti.com>
 * See LICENSE for license details.
 */

namespace Getnet\PaymentMagento\Gateway\Request\V1\TwoCc\Operations;

use Getnet\PaymentMagento\Gateway\Config\Config;
use Getnet\PaymentMagento\Gateway\Config\ConfigCc;
use InvalidArgumentException;
use Magento\Payment\Gateway\ConfigInterface;
use Magento\Payment\Gateway\Data\PaymentDataObjectInterface;
use Magento\Payment\Gateway\Request\BuilderInterface;

/**
 * Class Two Cc Refund Request - Refund data structure.
 */
class TwoCcRefundRequest implements BuilderInterface
{
    /**
     * External Payments - Block Name.
     */
    public const GETNET_PAYMENTS = 'payments';

    /**
     * External Payment Id - Block Name.
     */
    public const GETNET_PAYMENT_ID = 'payment_id';

    /**
     * Amount block name.
     */
    public const GETNET_PAYMENT_TAG = 'payment_tag';

    /**
     * @var ConfigInterface
     */
    protected $config;

    /**
     * @var Config
     */
    protected $configPayment;

    /**
     * @var ConfigCc
     */
    protected $configCc;

    /**
     * @param ConfigInterface $config
     * @param Config          $configPayment
     * @param ConfigCc        $configCc
     */
    public function __construct(
        ConfigInterface $config,
        Config $configPayment,
        ConfigCc $configCc
    ) {
        $this->config = $config;
        $this->configPayment = $configPayment;
        $this->configCc = $configCc;
    }

    /**
     * Build.
     *
     * @param array $buildSubject
     */
    public function build(array $buildSubject)
    {
        if (!isset($buildSubject['payment'])
            || !$buildSubject['payment'] instanceof PaymentDataObjectInterface
        ) {
            throw new InvalidArgumentException('Payment data object should be provided');
        }
        $result = [];

        $paymentDO = $buildSubject['payment'];

        $payment = $paymentDO->getPayment();

        $order = $paymentDO->getOrder();

        $incrementId = $order->getOrderIncrementId();

        $paymentId = $payment->getAdditionalInformation('payment_id');

        $tagId = $incrementId.'-1';

        $paymentIdSecondary = $payment->getAdditionalInformation('payment_id_secondary');

        $tagIdSecondary = $incrementId.'-2';

        $result[self::GETNET_PAYMENTS][] = [
            self::GETNET_PAYMENT_ID     => $paymentId,
            self::GETNET_PAYMENT_TAG    => $tagId,
        ];

        $result[self::GETNET_PAYMENTS][] = [
            self::GETNET_PAYMENT_ID     => $paymentIdSecondary,
            self::GETNET_PAYMENT_TAG    => $tagIdSecondary,
        ];

        return $result;
    }
}
