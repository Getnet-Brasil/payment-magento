<?php
/**
 * Copyright © Getnet. All rights reserved.
 *
 * @author    Bruno Elisei <brunoelisei@o2ti.com>
 * See LICENSE for license details.
 */

declare(strict_types=1);

namespace Getnet\PaymentMagento\Gateway\Response\Pix;

use Getnet\PaymentMagento\Gateway\SubjectReader;
use Magento\Payment\Gateway\Response\HandlerInterface;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Payment\Transaction;

/**
 * Persist the Biometric Pix charge on the payment and leave the order pending.
 */
class BiometricTxnIdHandler implements HandlerInterface
{
    public const RESPONSE_PAYMENT_ID = 'payment_id';

    public const RESPONSE_ADDITIONAL_DATA = 'additional_data';

    /**
     * Payment additional information keys.
     */
    public const INFO_JSR_URL = 'biometric_jsr_url';

    public const INFO_PAYMENT_LINK_ID = 'biometric_payment_link_id';

    public const INFO_TRANSACTION_ID = 'biometric_transaction_id';

    public const INFO_CREATION_DATE = 'biometric_creation_date';

    public const INFO_EXPIRATION_DATE = 'biometric_expiration_date';

    public const INFO_PSP_CODE = 'psp_code';

    /**
     * @var SubjectReader
     */
    protected $subjectReader;

    /**
     * @param SubjectReader $subjectReader
     */
    public function __construct(
        SubjectReader $subjectReader
    ) {
        $this->subjectReader = $subjectReader;
    }

    /**
     * Handle.
     *
     * @param array $handlingSubject
     * @param array $response
     *
     * @return void
     */
    public function handle(array $handlingSubject, array $response)
    {
        $payment = $this->subjectReader->readPayment($handlingSubject)->getPayment();
        $data = $response[self::RESPONSE_ADDITIONAL_DATA] ?? [];
        $paymentId = (string) $response[self::RESPONSE_PAYMENT_ID];
        $jsrUrl = (string) ($data['jsr_url'] ?? '');

        $payment->setAdditionalInformation(self::INFO_JSR_URL, $jsrUrl);
        $payment->setAdditionalInformation(self::INFO_PAYMENT_LINK_ID, $this->getPaymentLinkId($jsrUrl));
        $payment->setAdditionalInformation(self::INFO_TRANSACTION_ID, $data['transaction_id'] ?? null);
        $payment->setAdditionalInformation(self::INFO_CREATION_DATE, $data['creation_date'] ?? null);
        $payment->setAdditionalInformation(self::INFO_EXPIRATION_DATE, $data['expiration_date'] ?? null);
        $payment->setAdditionalInformation(self::INFO_PSP_CODE, $data['psp_code'] ?? null);

        $payment->setTransactionId($paymentId);
        $payment->setIsTransactionPending(1);
        $payment->setIsTransactionClosed(false);
        $payment->setAuthorizationTransaction($paymentId);
        $payment->addTransaction(Transaction::TYPE_AUTH);

        $order = $payment->getOrder();
        $order->setState(Order::STATE_NEW);
        $order->setStatus('pending');
        $order->addStatusHistoryComment(
            __('Awaiting authorization of the Pix by biometrics.'),
            $order->getStatus()
        );
    }

    /**
     * Extract the paymentLinkId query parameter of the journey url.
     *
     * @param string $jsrUrl
     *
     * @return string|null
     */
    public function getPaymentLinkId(string $jsrUrl): ?string
    {
        if (preg_match('/[?&]paymentLinkId=([^&#]+)/', $jsrUrl, $match)) {
            return rawurldecode($match[1]);
        }

        return null;
    }
}
