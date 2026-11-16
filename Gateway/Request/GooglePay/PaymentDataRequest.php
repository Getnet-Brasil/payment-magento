<?php
/**
 * Copyright © Getnet. All rights reserved.
 *
 * @author    Bruno Elisei <brunoelisei@o2ti.com>
 * See LICENSE for license details.
 */

declare(strict_types=1);

namespace Getnet\PaymentMagento\Gateway\Request\GooglePay;

use Getnet\PaymentMagento\Gateway\Config\ConfigGooglePay;
use Getnet\PaymentMagento\Gateway\Request\Card\CardInitSchemaDataRequest;
use Getnet\PaymentMagento\Gateway\Request\Card\Data\Payment\PaymentDataRequest as CardPaymentDataRequest;
use Magento\Framework\Exception\LocalizedException;
use Magento\Payment\Gateway\Data\PaymentDataObjectInterface;

/**
 * Class Payment Data Request - Google Pay payment structure (Authorization Token flow).
 *
 * The raw token returned by Google Pay is sent as is (Base64 encoded) in wallet.authorization_token;
 * no card data is ever handled by the module in this flow.
 */
class PaymentDataRequest extends CardPaymentDataRequest
{
    /**
     * Wallet - Block name.
     */
    public const WALLET = 'wallet';

    /**
     * Wallet Id - Block name.
     */
    public const WALLET_ID = 'id';

    /**
     * Wallet Type - Block name.
     */
    public const WALLET_TYPE = 'type';

    /**
     * Wallet Authorization Token - Block name.
     */
    public const AUTHORIZATION_TOKEN = 'authorization_token';

    /**
     * Build.
     *
     * @param array $buildSubject
     *
     * @throws LocalizedException
     *
     * @return array
     */
    public function build(array $buildSubject)
    {
        if (!isset($buildSubject['payment'])
            || !$buildSubject['payment'] instanceof PaymentDataObjectInterface
        ) {
            throw new \InvalidArgumentException('Payment data object should be provided');
        }

        $paymentDO = $this->subjectReader->readPayment($buildSubject);

        $payment = $paymentDO->getPayment();

        $order = $paymentDO->getOrder();

        $storeId = $order->getStoreId();

        $token = (string) $payment->getAdditionalInformation('googlepay_token');

        if ($token === '') {
            throw new LocalizedException(
                __('Could not validate the Google Pay payment. Please try again or choose another payment method.')
            );
        }

        // Single use token: never keep it after it has been placed in the request.
        $payment->unsAdditionalInformation('googlepay_token');

        $installment = (int) ($payment->getAdditionalInformation('cc_installments') ?: 1);

        $this->validateInstallment($installment, $storeId);

        $paymentId = $this->convertToGuid(
            $this->encrytor->hash($order->getOrderIncrementId(), 0)
        );

        $result = [];

        $result[CardInitSchemaDataRequest::DATA][self::PAYMENT] = [
            self::PAYMENT_ID          => $paymentId,
            self::PAYMENT_METHOD      => 'CREDIT',
            self::TRANSACTION_TYPE    => $this->configCc->getTypeInterestByInstallment($installment, $storeId),
            self::NUMBER_INSTALLMENTS => $installment,
            self::SOFT_DESCRIPTOR     => $this->config->getStatementDescriptor($storeId),
            self::DYNAMIC_MCC         => $this->config->getMerchantGatewayDynamicMcc($storeId),
            self::WALLET              => [
                self::WALLET_ID            => ConfigGooglePay::WALLET_ID,
                self::WALLET_TYPE          => ConfigGooglePay::WALLET_TYPE,
                self::AUTHORIZATION_TOKEN  => base64_encode($token),
            ],
        ];

        return $result;
    }

    /**
     * Validate the installment against the credit card settings (same rules as the Cc method).
     *
     * @param int      $installment
     * @param int|null $storeId
     *
     * @throws LocalizedException
     *
     * @return void
     */
    private function validateInstallment(int $installment, $storeId): void
    {
        $max = max(1, (int) $this->configCc->getMaxInstallment($storeId));

        if ($installment < 1 || $installment > $max) {
            throw new LocalizedException(
                __('Invalid installment for this order. Please select one of the available installments.')
            );
        }
    }
}
