<?php
/**
 * Copyright © Getnet. All rights reserved.
 *
 * @author    Bruno Elisei <brunoelisei@o2ti.com>
 * See LICENSE for license details.
 */

namespace Getnet\PaymentMagento\Gateway\Response\Pix;

use Getnet\PaymentMagento\Gateway\Config\Config;
use Getnet\PaymentMagento\Gateway\Config\ConfigPix;
use InvalidArgumentException;
use Magento\Payment\Gateway\Data\PaymentDataObjectInterface;
use Magento\Payment\Gateway\Response\HandlerInterface;
use Magento\Sales\Model\Order\Payment\Transaction;

/**
 * Txn Id Handler - Handles reading responses for Boleto payment.
 */
class TxnIdHandler implements HandlerInterface
{
    /**
     * Boleto Qr Code - Payment Addtional Information.
     */
    public const PAYMENT_INFO_QR_CODE = 'qr_code';

    /**
     * Creation Date Qrcode - Payment Addtional Information.
     */
    public const PAYMENT_INFO_CREATION_DATE_QRCODE = 'creation_date_qrcode';

    /**
     * Expiration Date Qrcode - Payment Addtional Information.
     */
    public const PAYMENT_INFO_EXPIRATION_DATE_QRCODE = 'expiration_date_qrcode';

    /**
     * Psp Code - Payment Addtional Information.
     */
    public const PAYMENT_INFO_PSP_CODE = 'psp_code';

    /**
     * Qr Code Image - Payment Addtional Information.
     */
    public const PAYMENT_INFO_QR_CODE_IMAGE = 'qr_code_image';

    /**
     * Response Pay Payment Id - Block name.
     */
    public const RESPONSE_PAYMENT_ID = 'payment_id';

    /**
     * Response Pay Pix - Block name.
     */
    public const RESPONSE_PIX = 'additional_data';

    /**
     * @var Config
     */
    private $config;

    /**
     * @var ConfigPix
     */
    private $configPix;

    /**
     * @param Config    $config
     * @param ConfigPix $configPix
     */
    public function __construct(
        Config $config,
        ConfigPix $configPix
    ) {
        $this->config = $config;
        $this->configPix = $configPix;
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

        $payPix = $response[self::RESPONSE_PIX];

        $qrCode = $payPix[self::PAYMENT_INFO_QR_CODE];

        $transactionId = $response[self::RESPONSE_PAYMENT_ID];

        $qrCodeImage = $this->configPix->generateImageQrCode($qrCode, $transactionId);

        $payment->setAdditionalInformation(
            self::PAYMENT_INFO_QR_CODE_IMAGE,
            $qrCodeImage
        );

        $payment->setAdditionalInformation(
            self::PAYMENT_INFO_QR_CODE,
            $qrCode
        );

        $payment->setAdditionalInformation(
            self::PAYMENT_INFO_CREATION_DATE_QRCODE,
            $payPix[self::PAYMENT_INFO_CREATION_DATE_QRCODE]
        );

        $payment->setAdditionalInformation(
            self::PAYMENT_INFO_EXPIRATION_DATE_QRCODE,
            $payPix[self::PAYMENT_INFO_EXPIRATION_DATE_QRCODE]
        );

        $payment->setAdditionalInformation(
            self::PAYMENT_INFO_PSP_CODE,
            $payPix[self::PAYMENT_INFO_PSP_CODE]
        );

        $payment->setTransactionId($transactionId);
        $payment->setIsTransactionPending(1);
        $payment->setIsTransactionClosed(false);
        $payment->setAuthorizationTransaction($transactionId);
        $payment->addTransaction(Transaction::TYPE_AUTH);

        $order = $payment->getOrder();
        $order->setState(\Magento\Sales\Model\Order::STATE_NEW);
        $order->setStatus('pending');
        $comment = __('Awaiting payment of the Pix.');
        $order->addStatusHistoryComment($comment, $payment->getOrder()->getStatus());
    }
}
