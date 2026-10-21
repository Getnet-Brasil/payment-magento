<?php
/**
 * Copyright © Getnet. All rights reserved.
 *
 * @author    Bruno Elisei <brunoelisei@o2ti.com>
 * See LICENSE for license details.
 */

declare(strict_types=1);

namespace Getnet\PaymentMagento\Gateway\Validator\GooglePay;

use Getnet\PaymentMagento\Gateway\Validator\GeneralResponseValidator;
use Magento\Payment\Gateway\Validator\ResultInterface;

/**
 * Class ResponseValidator - Handles return from gateway for Google Pay.
 *
 * Google Pay does not tell debit from credit, so a debit card can reach the gateway with installments.
 * When the gateway refuses an installment payment with a "wrong function / invalid installment" code,
 * the error is replaced by GOOGLEPAY-002 (see etc/getnet_error_mapping.xml) with a clear message.
 *
 * The module has no 3DS challenge: a PENDING answer asking for 3DS is refused (GOOGLEPAY-004) so no order is
 * left waiting for an authentication the customer can never complete.
 */
class ResponseValidator extends GeneralResponseValidator
{
    /**
     * Mapping code for "installments not available for this card".
     */
    public const ERROR_INSTALLMENT_NOT_ALLOWED = 'GOOGLEPAY-002';

    /**
     * Mapping code for "3DS authentication required but not supported".
     */
    public const ERROR_3DS_NOT_SUPPORTED = 'GOOGLEPAY-004';

    /**
     * Gateway codes that mean the card does not accept the requested function/installment.
     */
    protected const INSTALLMENT_REFUSED_CODES = [
        'PAYMENTS-083',
        'PAYMENTS-013',
    ];

    /**
     * Validate.
     *
     * @param array $validationSubject
     *
     * @return ResultInterface
     */
    public function validate(array $validationSubject): ResultInterface
    {
        $result = parent::validate($validationSubject);

        if ($result->isValid() && $this->requires3ds($validationSubject)) {
            return $this->createResult(false, [], [static::ERROR_3DS_NOT_SUPPORTED]);
        }

        if ($result->isValid() || !$this->hasInstallments($validationSubject)) {
            return $result;
        }

        $errorCodes = $result->getErrorCodes();

        if (array_intersect(static::INSTALLMENT_REFUSED_CODES, array_map('strval', $errorCodes))) {
            return $this->createResult(false, [], [static::ERROR_INSTALLMENT_NOT_ALLOWED]);
        }

        return $result;
    }

    /**
     * Whether the gateway answered PENDING with a 3DS challenge link.
     *
     * @param array $validationSubject
     *
     * @return bool
     */
    private function requires3ds(array $validationSubject): bool
    {
        $response = $validationSubject['response'] ?? [];

        if (($response['status'] ?? null) !== 'PENDING') {
            return false;
        }

        $links = $response['additional_data']['_links'] ?? [];

        return strpos((string) json_encode($links), '3ds_html') !== false;
    }

    /**
     * Whether the payment was placed with more than one installment.
     *
     * @param array $validationSubject
     *
     * @return bool
     */
    private function hasInstallments(array $validationSubject): bool
    {
        if (!isset($validationSubject['payment'])) {
            return false;
        }

        $installments = $validationSubject['payment']->getPayment()->getAdditionalInformation('cc_installments');

        return (int) $installments > 1;
    }
}
