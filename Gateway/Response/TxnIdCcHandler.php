<?php
/**
 * Copyright © Getnet. All rights reserved.
 *
 * @author    Bruno Elisei <brunoelisei@o2ti.com>
 * See LICENSE for license details.
 */

namespace Getnet\PaymentMagento\Gateway\Response;

use Getnet\PaymentMagento\Gateway\Config\Config;
use Getnet\PaymentMagento\Gateway\Config\ConfigCc;
use InvalidArgumentException;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Payment\Gateway\Data\PaymentDataObjectInterface;
use Magento\Payment\Gateway\Response\HandlerInterface;

/**
 * Txn Id Cc Handler - Handles reading responses for Boleto payment.
 */
class TxnIdCcHandler implements HandlerInterface
{
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

        $paymentDO = $handlingSubject['payment'];

        $payment = $paymentDO->getPayment();

        $payCredit = $response[self::CREDIT];

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

        $ccType = $payment->getAdditionalInformation(self::PAYMENT_INFO_CC_TYPE);
        if ($ccType) {
            $ccType = $this->getCreditCardType($ccType);
            $payment->setCcType($ccType);
        }

        $ccLast4 = $payment->getAdditionalInformation(self::PAYMENT_INFO_CC_NUMBER);
        $ccLast4 = preg_replace('/[^0-9]/', '', $ccLast4);
        $payment->setCcLast4($ccLast4);

        $ccOwner = $payment->getAdditionalInformation(self::PAYMENT_INFO_CC_OWNER);
        $payment->setCcOwner($ccOwner);

        $ccExpMonth = $payment->getAdditionalInformation(self::PAYMENT_INFO_CC_EXP_MONTH);
        $payment->setCcExpMonth($ccExpMonth);

        $ccExpYear = $payment->getAdditionalInformation(self::PAYMENT_INFO_CC_EXP_YEAR);
        $payment->setCcExpYear($ccExpYear);

        $ccNumberEnc = $payment->getAdditionalInformation(self::PAYMENT_INFO_CC_NUMBER_ENC);
        $payment->setCcNumberEnc($ccNumberEnc);
    }

    /**
     * Get type of credit card mapped from Getnet.
     *
     * @param string $type
     *
     * @return string
     */
    public function getCreditCardType(string $type): ?string
    {
        $mapper = $this->configCc->getCcTypesMapper();

        return $mapper[$type];
    }
}
