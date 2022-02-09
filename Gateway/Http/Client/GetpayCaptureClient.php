<?php
/**
 * Copyright © Getnet. All rights reserved.
 *
 * @author    Bruno Elisei <brunoelisei@o2ti.com>
 * See LICENSE for license details.
 */

declare(strict_types=1);

namespace Getnet\PaymentMagento\Gateway\Http\Client;

use Getnet\PaymentMagento\Gateway\Request\ExtPaymentIdRequest;
use Magento\Payment\Gateway\Http\ClientInterface;
use Magento\Payment\Gateway\Http\TransferInterface;

/**
 * Class Getpay Capture Client - Returns capture authorization.
 *
 * @SuppressWarnings(PHPCPD)
 */
class GetpayCaptureClient implements ClientInterface
{
    /**
     * Result Code - Block name.
     */
    public const RESULT_CODE = 'RESULT_CODE';

    /**
     * Response Pay Payment Id - Block name.
     */
    public const RESPONSE_PAYMENT_ID = 'payment_id';

    /**
     * Places request to gateway.
     *
     * @param TransferInterface $transferObject
     *
     * @return array
     */
    public function placeRequest(TransferInterface $transferObject)
    {
        $request = $transferObject->getBody();

        $paymentId = $request[ExtPaymentIdRequest::GETNET_PAYMENT_ID];

        $response = [
            self::RESULT_CODE         => 1,
            self::RESPONSE_PAYMENT_ID => $paymentId,
        ];

        return $response;
    }
}
