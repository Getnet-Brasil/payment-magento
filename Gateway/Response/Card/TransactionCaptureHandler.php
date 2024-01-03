<?php
/**
 * Copyright © Getnet. All rights reserved.
 *
 * @author    Bruno Elisei <brunoelisei@o2ti.com>
 * See LICENSE for license details.
 */

namespace Getnet\PaymentMagento\Gateway\Response\Card;

use Getnet\PaymentMagento\Gateway\Config\Config;
use Getnet\PaymentMagento\Gateway\Config\ConfigCc;
use InvalidArgumentException;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Payment\Gateway\Data\PaymentDataObjectInterface;
use Magento\Payment\Gateway\Response\HandlerInterface;

/**
 * Transaction Capture Handler - Handles reading responses for CC on capture.
 *
 * @SuppressWarnings(PHPCPD)
 */
class TransactionCaptureHandler implements HandlerInterface
{
    /**
     * Response Pay Credit - Block name.
     */
    public const CREDIT = 'credit';

    /**
     * Response Pay Payment Id - Block name.
     */
    public const RESPONSE_PAYMENT_ID = 'payment_id';

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
        $isApproved = false;

        $isDenied = true;

        $paymentDO = $handlingSubject['payment'];

        $payment = $paymentDO->getPayment();

        $order = $payment->getOrder();

        $amount = $order->getBaseGrandTotal();

        $baseAmount = $order->getGrandTotal();

        if ($response[self::RESPONSE_STATUS] === self::RESPONSE_APPROVED ||
            $response[self::RESPONSE_STATUS] === self::RESPONSE_AUTHORIZED ||
            $response[self::RESPONSE_STATUS] === self::RESPONSE_PENDING
        ) {
            $isApproved = true;
            $isDenied = false;
        }

        $transactionId = $response[self::RESPONSE_PAYMENT_ID];
        $payment->setAuthorizationTransaction($transactionId);
        $payment->registerAuthorizationNotification($amount);
        $payment->setAmountAuthorized($amount);
        $payment->setBaseAmountAuthorized($baseAmount);
        $payment->setIsTransactionApproved($isApproved);
        $payment->setIsTransactionDenied($isDenied);
        $payment->registerCaptureNotification($amount);
        $payment->setTransactionId($transactionId);
        $payment->setTransactionDetails($this->json->serialize($response));
        $payment->setAdditionalData($this->json->serialize($response));
    }
}
