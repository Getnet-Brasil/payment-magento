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
     * Response Pay Payments - Block Name.
     */
    public const RESPONSE_PAYMENTS = 'payments';

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
        $request = $transferObject->getBody();
        $path = 'v1/payments/combined/confirm';

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
        if (isset($data[self::RESPONSE_PAYMENTS]) &&
            $data[self::RESPONSE_PAYMENTS][0][self::RESPONSE_STATUS] === self::RESPONSE_STATUS_CONFIRMED
        ) {
            $response = array_merge(
                [
                    self::RESULT_CODE => 1,
                ],
                $data
            );
        }

        return $response;
    }
}
