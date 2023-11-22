<?php
/**
 * Copyright © Getnet. All rights reserved.
 *
 * @author    Bruno Elisei <brunoelisei@o2ti.com>
 * See LICENSE for license details.
 */

declare(strict_types=1);

namespace Getnet\PaymentMagento\Gateway\Http\Client;

use Magento\Payment\Gateway\Http\ClientInterface;
use Magento\Payment\Gateway\Http\TransferInterface;
use Getnet\PaymentMagento\Gateway\Http\Api;
use Getnet\PaymentMagento\Gateway\Request\ExtPaymentIdRequest;

/**
 * Class Accept Payment Client - Returns authorization to accept payment.
 *
 * @SuppressWarnings(PHPCPD)
 */
class AcceptPaymentClient implements ClientInterface
{
    /**
     * Result Code - Block name.
     */
    public const RESULT_CODE = 'RESULT_CODE';

    /**
     * Store Id - Block name.
     */
    public const STORE_ID = 'store_id';

    /**
     * Response Pay Status - Block Name.
     */
    public const RESPONSE_STATUS = 'status';

    /**
     * Response Pay Status Approved - Value.
     */
    public const RESPONSE_STATUS_CONFIRMED = 'CONFIRMED';

    /**
     * Response Pay Status Denied - Value.
     */
    public const RESPONSE_STATUS_ERROR = 'ERROR';

    /**
     * @var Api
     */
    protected $api;

    /**
     * @param Api $api
     */
    public function __construct(
        Api $api
    ) {
        $this->api = $api;
    }

    /**
     * Places request to gateway.
     *
     * @param TransferInterface $transferObject
     *
     * @return array
     */
    public function placeRequest(TransferInterface $transferObject)
    {
        $status = 0;
        $request = $transferObject->getBody();
        $paymentId = $request[ExtPaymentIdRequest::GETNET_PAYMENT_ID];
        unset($request[ExtPaymentIdRequest::GETNET_PAYMENT_ID]);
        
        $responseBody = $this->api->sendPostRequest(
            $transferObject,
            'v1/payments/credit/'.$paymentId.'/confirm',
            $request,
        );

        if (isset($responseBody[self::RESPONSE_STATUS]) &&
                $responseBody[self::RESPONSE_STATUS] === self::RESPONSE_STATUS_CONFIRMED
            ) {
            $status = 1;
        }

        $response = array_merge(
            [
                self::RESULT_CODE => $status,
            ],
            $responseBody
        );

        return $response;
    }
}
