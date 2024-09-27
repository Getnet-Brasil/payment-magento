<?php
/**
 * Copyright © Getnet. All rights reserved.
 *
 * @author    Bruno Elisei <brunoelisei@o2ti.com>
 * See LICENSE for license details.
 */

declare(strict_types=1);

namespace Getnet\PaymentMagento\Gateway\Http\Client\V1\TwoCc\Operations;

use Getnet\PaymentMagento\Gateway\Http\Api;
use Magento\Payment\Gateway\Http\ClientInterface;
use Magento\Payment\Gateway\Http\TransferInterface;

/**
 * Class Deny Payment Client - Returns authorization to denied payment.
 *
 * @SuppressWarnings(PHPCPD)
 */
class DenyPaymentClient implements ClientInterface
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
     * Day Zero block name.
     */
    public const DAY_ZERO = 'day_zero';

    /**
     * Response Pay Payments - Block Name.
     */
    public const RESPONSE_PAYMENTS = 'payments';

    /**
     * Response Pay Status - Block Name.
     */
    public const RESPONSE_STATUS = 'status';

    /**
     * Response Pay Status Denied - Value.
     */
    public const RESPONSE_STATUS_DENIED = 'DENIED';

    /**
     * Response Pay Cancel Request Id - Block name.
     */
    public const RESPONSE_CANCEL_REQUEST_ID = 'cancel_request_id';

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
        $request = $transferObject->getBody();
        $context = [];
        $paymentId = $request['payment_id'];
        $path = 'v1/payments/combined/cancel/request';

        if ($request[self::DAY_ZERO]) {
            $path = 'v1/payments/combined/cancel';
        }

        unset($request['payment_id']);
        unset($request[self::CANCEL_AMOUNT]);
        unset($request[self::DAY_ZERO]);

        $data = $this->api->sendPostRequest(
            $transferObject,
            $path,
            $request,
        );

        $response = array_merge(
            [
                self::RESULT_CODE => 0,
            ],
            $data
        );

        if (isset($data[self::RESPONSE_PAYMENTS])) {
            $context = $data[self::RESPONSE_PAYMENTS][0];
        }

        if (isset($data[self::RESPONSE_PAYMENTS][0])) {
            $response = array_merge(
                [
                    self::RESULT_CODE                 => 1,
                    self::RESPONSE_CANCEL_REQUEST_ID  => $context[self::RESPONSE_CANCEL_REQUEST_ID],
                ],
                $data
            );
            if ($context[self::RESPONSE_STATUS] === self::RESPONSE_STATUS_DENIED) {
                $response = array_merge(
                    [
                        self::RESULT_CODE                 => 0,
                        self::RESPONSE_CANCEL_REQUEST_ID  => $context[self::RESPONSE_CANCEL_REQUEST_ID],
                    ],
                    $data
                );
            }
        }

        if (isset($context[self::RESPONSE_STATUS])) {
            if ($context[self::RESPONSE_STATUS] === 'CANCELED') {
                $response = array_merge(
                    [
                        self::RESULT_CODE                 => 1,
                        self::RESPONSE_CANCEL_REQUEST_ID  => $paymentId.'-cancel',
                    ],
                    $data
                );
            }
        }

        return $response;
    }
}
