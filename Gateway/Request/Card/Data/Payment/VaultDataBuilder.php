<?php
/**
 * Copyright © Getnet. All rights reserved.
 *
 * @author    Bruno Elisei <brunoelisei@o2ti.com>
 * See LICENSE for license details.
 */

namespace Getnet\PaymentMagento\Gateway\Request\Card\Data\Payment;

use Getnet\PaymentMagento\Gateway\Request\Card\CardInitSchemaDataRequest;
use InvalidArgumentException;
use Magento\Payment\Gateway\Data\PaymentDataObjectInterface;
use Magento\Payment\Gateway\Request\BuilderInterface;
use Magento\Payment\Model\InfoInterface;
use Magento\Vault\Model\Ui\VaultConfigProvider;

class VaultDataBuilder implements BuilderInterface
{
    /**
     * Credit - Block name.
     */
    public const CREDIT = 'credit';

    /**
     * Save Card Data - Block name.
     */
    public const SAVE_CARD_DATA = 'save_card_data';

    /**
     * The option that determines whether the payment method associated with
     * the successful transaction should be stored in the Vault.
     */
    public const STORE_IN_VAULT_ON_SUCCESS = 'true';

    /**
     * Build.
     *
     * @param array $buildSubject
     */
    public function build(array $buildSubject): array
    {
        if (!isset($buildSubject['payment'])
            || !$buildSubject['payment'] instanceof PaymentDataObjectInterface
        ) {
            throw new InvalidArgumentException('Payment data object should be provided');
        }

        $result = [];

        $save = false;

        $paymentDO = $buildSubject['payment'];

        /** @var InfoInterface $payment * */
        $payment = $paymentDO->getPayment();

        if (!empty($payment->getAdditionalInformation(VaultConfigProvider::IS_ACTIVE_CODE))) {
            $save = self::STORE_IN_VAULT_ON_SUCCESS;
        }

        $result[CardInitSchemaDataRequest::DATA][PaymentDataRequest::PAYMENT] = [
            self::SAVE_CARD_DATA => $save,
        ];

        return $result;
    }
}
