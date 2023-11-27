<?php
/**
 * Copyright © Getnet. All rights reserved.
 *
 * @author    Bruno Elisei <brunoelisei@o2ti.com>
 * See LICENSE for license details.
 */

declare(strict_types=1);

namespace Getnet\PaymentMagento\Gateway\Http\Client;

use Getnet\PaymentMagento\Gateway\Http\Api;
use Magento\Payment\Gateway\Http\ClientInterface;
use Magento\Payment\Gateway\Http\TransferInterface;

/**
 * Class Deny Payment Client - Create authorization for payment deny.
 *
 * @SuppressWarnings(PHPCPD)
 */
class DenyPaymentClient implements ClientInterface
{
    /**
     * External Payment Id - Block Name.
     */
    public const GETNET_PAYMENT_ID = 'payment_id';

    /**
     * Day Zero block name.
     */
    public const DAY_ZERO = 'day_zero';

    /**
     * Result Code - Block name.
     */
    public const RESULT_CODE = 'RESULT_CODE';

    /**
     * Store Id - Block name.
     */
    public const STORE_ID = 'store_id';

    /**
     * Response Pay Cancel Request Id - Block name.
     */
    public const RESPONSE_CANCEL_REQUEST_ID = 'cancel_request_id';

    /**
     * Response Pay Status - Block Name.
     */
    public const RESPONSE_STATUS = 'status';

    /**
     * Response Pay Status Denied - Value.
     */
    public const RESPONSE_STATUS_DENIED = 'DENIED';

    /**
     * Amount block name.
     */
    public const CANCEL_AMOUNT = 'cancel_amount';

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
        $response = [];
        $request = $transferObject->getBody();
        $path = 'v1/payments/cancel';
        $paymentId = $request[self::GETNET_PAYMENT_ID];

        if ($request[self::DAY_ZERO]) {
            unset($request[self::CANCEL_AMOUNT]);
        }

        unset($request[self::DAY_ZERO]);

        $responseBody = $this->api->sendPostRequest(
            $transferObject,
            $path,
            $request,
        );

        if (isset($responseBody[self::RESPONSE_CANCEL_REQUEST_ID])) {
            $response = array_merge(
                [
                    self::RESULT_CODE                 => 1,
                    self::RESPONSE_CANCEL_REQUEST_ID  => $responseBody[self::RESPONSE_CANCEL_REQUEST_ID],
                ],
                $responseBody
            );
            if ($responseBody[self::RESPONSE_STATUS] === self::RESPONSE_STATUS_DENIED) {
                $response = array_merge(
                    [
                        self::RESULT_CODE                 => 0,
                        self::RESPONSE_CANCEL_REQUEST_ID  => $responseBody[self::RESPONSE_CANCEL_REQUEST_ID],
                    ],
                    $responseBody
                );
            }
        }

        if (isset($responseBody[self::RESPONSE_STATUS])) {
            if ($responseBody[self::RESPONSE_STATUS] === 'CANCELED') {
                $response = array_merge(
                    [
                        self::RESULT_CODE                 => 1,
                        self::RESPONSE_CANCEL_REQUEST_ID  => $paymentId.'-cancel',
                    ],
                    $responseBody
                );
            }
        }

        return $response;
    }
}
