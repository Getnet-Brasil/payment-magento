<?php
/**
 * Copyright © Getnet. All rights reserved.
 *
 * @author    Bruno Elisei <brunoelisei@o2ti.com>
 * See LICENSE for license details.
 */

namespace Getnet\PaymentMagento\Gateway\Response\V1\TwoCc;

use Getnet\PaymentMagento\Gateway\Config\Config;
use Getnet\PaymentMagento\Gateway\Config\ConfigCc;
use InvalidArgumentException;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Payment\Gateway\Data\PaymentDataObjectInterface;
use Magento\Payment\Gateway\Response\HandlerInterface;

/**
 * Txn Id Handler - Handles reading responses for Two Cc payment.
 */
class TxnIdHandler implements HandlerInterface
{
    /**
     * Cc Payment Id - Payment Addtional Information.
     */
    public const PAYMENT_INFO_PAYMENT_ID = 'payment_id';

    /**
     * Cc Terminal NSU - Payment Addtional Information.
     */
    public const PAYMENT_INFO_TERMINAL_NSU = 'terminal_nsu';

    /**
     * Cc Authorization Code - Payment Addtional Information.
     */
    public const PAYMENT_INFO_AUTHORIZATION_CODE = 'authorization_code';

    /**
     * Cc Acquirer Transaction Id - Payment Addtional Information.
     */
    public const PAYMENT_INFO_ACQUIRER_TRANSACTION_ID = 'acquirer_transaction_id';

    /**
     * Cc Transaction Id - Payment Addtional Information.
     */
    public const PAYMENT_INFO_TRANSACTION_ID = 'transaction_id';

    /**
     * Cc Payment Id Secondary - Payment Addtional Information.
     */
    public const PAYMENT_INFO_PAYMENT_ID_SECONDARY = 'payment_id_secondary';

    /**
     * Cc Terminal NSU Secondary - Payment Addtional Information.
     */
    public const PAYMENT_INFO_TERMINAL_NSU_SECONDARY = 'terminal_nsu_secondary';

    /**
     * Cc Authorization Code Secondary - Payment Addtional Information.
     */
    public const PAYMENT_INFO_AUTHORIZATION_CODE_SECONDARY = 'authorization_code_secondary';

    /**
     * Cc Acquirer Transaction Id Secondary - Payment Addtional Information.
     */
    public const PAYMENT_INFO_ACQUIRER_TRANSACTION_ID_SECONDARY = 'acquirer_transaction_id_secondary';

    /**
     * Cc Transaction Id Secondary - Payment Addtional Information.
     */
    public const PAYMENT_INFO_TRANSACTION_ID_SECONDARY = 'transaction_id_secondary';

    /**
     * Cc Type - Payment Addtional Information.
     */
    public const PAYMENT_INFO_CC_TYPE = 'cc_type';

    /**
     * Cc Number - Payment Addtional Information.
     */
    public const PAYMENT_INFO_CC_NUMBER = 'cc_number';

    /**
     * Cc Owner - Payment Addtional Information.
     */
    public const PAYMENT_INFO_CC_OWNER = 'cc_holder_fullname';

    /**
     * Cc Exp Month - Payment Addtional Information.
     */
    public const PAYMENT_INFO_CC_EXP_MONTH = 'cc_exp_month';

    /**
     * Cc Exp Year - Payment Addtional Information.
     */
    public const PAYMENT_INFO_CC_EXP_YEAR = 'cc_exp_year';

    /**
     * Cc Number Token - Payment Addtional Information.
     */
    public const PAYMENT_INFO_CC_NUMBER_ENC = 'cc_hash';

    /**
     * Response Payments - Block name.
     */
    public const PAYMENTS = 'payments';

    /**
     * Response Pay Credit - Block name.
     */
    public const CREDIT = 'credit';

    /**
     * Response Pay Payment Id - Block name.
     */
    public const RESPONSE_PAYMENT_ID = 'payment_id';

    /**
     * Response Pay Terminal NSU - Block name.
     */
    public const RESPONSE_TERMINAL_NSU = 'terminal_nsu';

    /**
     * Response Pay Authorization Code - Block name.
     */
    public const RESPONSE_AUTHORIZATION_CODE = 'authorization_code';

    /**
     * Response Pay Acquirer Transaction Id - Block name.
     */
    public const RESPONSE_ACQUIRER_TRANSACTION_ID = 'acquirer_transaction_id';

    /**
     * Response Pay Transaction Id - Block name.
     */
    public const RESPONSE_TRANSACTION_ID = 'transaction_id';

    /**
     * Response Pay Delayed - Block name.
     */
    public const RESPONSE_DELAYED = 'delayed';

    /**
     * Response Pay Status - Block name.
     */
    public const RESPONSE_STATUS = 'status';

    /**
     * Response Pay Approved - Block name.
     */
    public const RESPONSE_APPROVED = 'APPROVED';

    /**
     * Response Pay Authorized - Block name.
     */
    public const RESPONSE_AUTHORIZED = 'AUTHORIZED';

    /**
     * Response Pay Pending - Block name.
     */
    public const RESPONSE_PENDING = 'PENDING';

    /**
     * @var Json
     */
    private $json;

    /**
     * @var Config
     */
    private $config;

    /**
     * @var ConfigCc
     */
    private $configCc;

    /**
     * @param Json     $json
     * @param Config   $config
     * @param ConfigCc $configCc
     */
    public function __construct(
        Json $json,
        Config $config,
        ConfigCc $configCc
    ) {
        $this->json = $json;
        $this->config = $config;
        $this->configCc = $configCc;
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

        $idx = 0;

        $payments = $response[self::PAYMENTS];

        foreach ($payments as $paymentIdx) {
            $this->setDataForPaymentIdx($idx, $handlingSubject, $paymentIdx);
            $idx++;
        }
    }

    /**
     * Set Data For Payment Idx.
     *
     * @param int   $idx
     * @param array $handlingSubject
     * @param array $paymentIdx
     *
     * @return void
     */
    public function setDataForPaymentIdx(
        int $idx,
        array $handlingSubject,
        array $paymentIdx
    ) {
        $paymentDO = $handlingSubject['payment'];
        $payment = $paymentDO->getPayment();
        $payCredit = $paymentIdx[self::CREDIT];
        if ($idx === 0) {
            $payment->setAdditionalInformation(
                self::PAYMENT_INFO_PAYMENT_ID,
                $paymentIdx[self::RESPONSE_PAYMENT_ID]
            );

            $payment->setAdditionalInformation(
                self::PAYMENT_INFO_TERMINAL_NSU,
                $payCredit[self::RESPONSE_TERMINAL_NSU]
            );

            $payment->setAdditionalInformation(
                self::PAYMENT_INFO_AUTHORIZATION_CODE,
                $payCredit[self::RESPONSE_AUTHORIZATION_CODE]
            );

            $payment->setAdditionalInformation(
                self::PAYMENT_INFO_ACQUIRER_TRANSACTION_ID,
                $payCredit[self::RESPONSE_ACQUIRER_TRANSACTION_ID]
            );

            $payment->setAdditionalInformation(
                self::PAYMENT_INFO_TRANSACTION_ID,
                $payCredit[self::RESPONSE_TRANSACTION_ID]
            );
        }

        if ($idx === 1) {
            $payment->setAdditionalInformation(
                self::PAYMENT_INFO_PAYMENT_ID_SECONDARY,
                $paymentIdx[self::RESPONSE_PAYMENT_ID]
            );

            $payment->setAdditionalInformation(
                self::PAYMENT_INFO_TERMINAL_NSU_SECONDARY,
                $payCredit[self::RESPONSE_TERMINAL_NSU]
            );

            $payment->setAdditionalInformation(
                self::PAYMENT_INFO_AUTHORIZATION_CODE_SECONDARY,
                $payCredit[self::RESPONSE_AUTHORIZATION_CODE]
            );

            $payment->setAdditionalInformation(
                self::PAYMENT_INFO_ACQUIRER_TRANSACTION_ID_SECONDARY,
                $payCredit[self::RESPONSE_ACQUIRER_TRANSACTION_ID]
            );

            $payment->setAdditionalInformation(
                self::PAYMENT_INFO_TRANSACTION_ID_SECONDARY,
                $payCredit[self::RESPONSE_TRANSACTION_ID]
            );
        }
    }
}
