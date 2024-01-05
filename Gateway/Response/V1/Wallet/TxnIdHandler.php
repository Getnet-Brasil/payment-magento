<?php
/**
 * Copyright © Getnet. All rights reserved.
 *
 * @author    Bruno Elisei <brunoelisei@o2ti.com>
 * See LICENSE for license details.
 */

namespace Getnet\PaymentMagento\Gateway\Response\V1\Wallet;

use Getnet\PaymentMagento\Gateway\Config\Config;
use Getnet\PaymentMagento\Gateway\Config\ConfigWallet;
use InvalidArgumentException;
use Magento\Payment\Gateway\Data\PaymentDataObjectInterface;
use Magento\Payment\Gateway\Response\HandlerInterface;
use Magento\Sales\Model\Order\Payment\Transaction;

/**
 * Txn Id Handler - Handles reading responses for Wallet payment.
 */
class TxnIdHandler implements HandlerInterface
{
    /**
     * Boleto Qr Code - Payment Addtional Information.
     */
    public const PAYMENT_INFO_QR_CODE = 'qr_code';

    /**
     * Qr Code Image - Payment Addtional Information.
     */
    public const PAYMENT_INFO_QR_CODE_IMAGE = 'qr_code_image';

    /**
     * Response Pay Payment Id - Block name.
     */
    public const RESPONSE_PAYMENT_ID = 'payment_id';

    /**
     * @var Config
     */
    private $config;

    /**
     * @var ConfigWallet
     */
    private $configWallet;

    /**
     * @param Config       $config
     * @param ConfigWallet $configWallet
     */
    public function __construct(
        Config $config,
        ConfigWallet $configWallet
    ) {
        $this->config = $config;
        $this->configWallet = $configWallet;
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

        $qrCode = $response[self::PAYMENT_INFO_QR_CODE];

        $transactionId = $response[self::RESPONSE_PAYMENT_ID];

        $qrCodeImage = $this->configWallet->generateImageQrCode($qrCode, $transactionId);

        $payment->setAdditionalInformation(
            self::PAYMENT_INFO_QR_CODE_IMAGE,
            $qrCodeImage
        );

        $payment->setAdditionalInformation(
            self::PAYMENT_INFO_QR_CODE,
            $qrCode
        );

        $payment->setTransactionId($transactionId);
        $payment->setIsTransactionPending(1);
        $payment->setIsTransactionClosed(false);
        $payment->setAuthorizationTransaction($transactionId);
        $payment->addTransaction(Transaction::TYPE_AUTH);

        $order = $payment->getOrder();
        $order->setState(\Magento\Sales\Model\Order::STATE_NEW);
        $order->setStatus('pending');
        $comment = __('Awaiting payment of the Wallet.');
        $order->addStatusHistoryComment($comment, $payment->getOrder()->getStatus());
    }
}
