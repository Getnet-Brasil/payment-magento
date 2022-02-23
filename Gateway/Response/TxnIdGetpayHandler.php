<?php
/**
 * Copyright © Getnet. All rights reserved.
 *
 * @author    Bruno Elisei <brunoelisei@o2ti.com>
 * See LICENSE for license details.
 */

namespace Getnet\PaymentMagento\Gateway\Response;

use Getnet\PaymentMagento\Gateway\Config\Config;
use Getnet\PaymentMagento\Gateway\Config\ConfigBoleto;
use InvalidArgumentException;
use Magento\Payment\Gateway\Data\PaymentDataObjectInterface;
use Magento\Payment\Gateway\Response\HandlerInterface;
use Magento\Sales\Model\Order\Payment\Transaction;

/**
 * Txn Id Getpay Handler - Handles reading responses for Getpay payment.
 */
class TxnIdGetpayHandler implements HandlerInterface
{
    /**
     * Getpay Link - Payment Addtional Information.
     */
    public const PAYMENT_INFO_GETPAY_LINK = 'getpay_link';

    /**
     * Getpay Expiration Date - Payment Addtional Information.
     */
    public const PAYMENT_INFO_GETPAY_EXPIRATION_DATE = 'getpay_expiration_date';

    /**
     * Response Pay Link Id - Block name.
     */
    public const RESPONSE_LINK_ID = 'link_id';

    /**
     * Response Pay Url - Block name.
     */
    public const RESPONSE_URL = 'url';

    /**
     * Response Pay Expiration - Block name.
     */
    public const RESPONSE_GETPAY_EXPIRATION = 'expiration';

    /**
     * @var Config
     */
    protected $config;

    /**
     * @var ConfigBoleto
     */
    protected $configBoleto;

    /**
     * @param Config       $config
     * @param ConfigBoleto $configBoleto
     */
    public function __construct(
        Config $config,
        ConfigBoleto $configBoleto
    ) {
        $this->config = $config;
        $this->configBoleto = $configBoleto;
    }

    /**
     * Handles.
     *
     * @param array $handlingSubject
     * @param array $response
     *
     * @return void
     */
    public function handle(array $handlingSubject, array $response)
    {
        if (!isset($handlingSubject['payment'])
            || !$handlingSubject['payment'] instanceof PaymentDataObjectInterface
        ) {
            throw new InvalidArgumentException('Payment data object should be provided');
        }

        $paymentDO = $handlingSubject['payment'];

        $payment = $paymentDO->getPayment();

        $payment->setAdditionalInformation(
            self::PAYMENT_INFO_GETPAY_LINK,
            $response[self::RESPONSE_URL]
        );

        $payment->setAdditionalInformation(
            self::PAYMENT_INFO_GETPAY_EXPIRATION_DATE,
            str_replace($response[self::RESPONSE_GETPAY_EXPIRATION], 'Z', '')
        );

        $transactionId = $response[self::RESPONSE_LINK_ID];
        $payment->setTransactionId($transactionId);
        $payment->setIsTransactionPending(1);
        $payment->setIsTransactionClosed(false);
        $payment->setAuthorizationTransaction($transactionId);
        $payment->addTransaction(Transaction::TYPE_AUTH);

        $order = $payment->getOrder();
        $order->setState(\Magento\Sales\Model\Order::STATE_NEW);
        $order->setStatus('pending');
        $comment = __('Awaiting payment of the Getpay.');
        $order->addStatusHistoryComment($comment, $payment->getOrder()->getStatus());
    }
}
