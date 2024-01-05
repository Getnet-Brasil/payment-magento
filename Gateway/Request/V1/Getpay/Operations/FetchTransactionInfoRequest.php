<?php
/**
 * Copyright © Getnet. All rights reserved.
 *
 * @author    Bruno Elisei <brunoelisei@o2ti.com>
 * See LICENSE for license details.
 */

namespace Getnet\PaymentMagento\Gateway\Request\V1\Getpay\Operations;

use InvalidArgumentException;
use Magento\Payment\Gateway\ConfigInterface;
use Magento\Payment\Gateway\Data\PaymentDataObjectInterface;
use Magento\Payment\Gateway\Request\BuilderInterface;

/**
 * Class Fetch Transaction Info Request - Transaction query data structure.
 */
class FetchTransactionInfoRequest implements BuilderInterface
{
    /**
     * @var string
     */
    public const GETNET_ORDER_ID = 'order_id';

    /**
     * Order State - Block Name.
     */
    public const ORDER_STATE = 'state';

    /**
     * @var ConfigInterface
     */
    private $config;

    /**
     * @param ConfigInterface $config
     */
    public function __construct(
        ConfigInterface $config
    ) {
        $this->config = $config;
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

        $paymentDO = $buildSubject['payment'];

        $payment = $paymentDO->getPayment();

        $order = $payment->getOrder();

        return [
            self::GETNET_ORDER_ID   => $order->getExtOrderId(),
            self::ORDER_STATE       => $order->getState(),
        ];
    }
}
