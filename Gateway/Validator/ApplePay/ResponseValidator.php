<?php
/**
 * Copyright © Getnet. All rights reserved.
 *
 * @author    Bruno Elisei <brunoelisei@o2ti.com>
 * See LICENSE for license details.
 */

declare(strict_types=1);

namespace Getnet\PaymentMagento\Gateway\Validator\ApplePay;

use Getnet\PaymentMagento\Gateway\Validator\GooglePay\ResponseValidator as WalletResponseValidator;

/**
 * Class ResponseValidator - Handles return from gateway for Apple Pay.
 *
 * Same rules of the Google Pay wallet (installments on debit cards, no 3DS challenge), with Apple Pay messages.
 */
class ResponseValidator extends WalletResponseValidator
{
    /**
     * Mapping code for "installments not available for this card".
     */
    public const ERROR_INSTALLMENT_NOT_ALLOWED = 'APPLEPAY-002';

    /**
     * Mapping code for "3DS authentication required but not supported".
     */
    public const ERROR_3DS_NOT_SUPPORTED = 'APPLEPAY-004';
}
