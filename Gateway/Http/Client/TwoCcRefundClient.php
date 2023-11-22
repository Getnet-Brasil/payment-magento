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

/**
 * Class Two Cc Refund Client - Returns refund data.
 *
 * @SuppressWarnings(PHPCPD)
 */
class TwoCcRefundClient implements ClientInterface
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
     * Response Payments- Block name.
     */
    public const RESPONSE_PAYMENTS = 'payments';

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
        $path = 'v1/payments/combined/cancel';
        
        $data = $this->api->sendGetRequest(
            $transferObject,
            $path,
            $request,
        );

        $response = array_merge(
            [
                self::RESULT_CODE  => 0,
            ],
            $data
        );
        if (isset($data[self::RESPONSE_PAYMENTS])) {
            $response = array_merge(
                [
                    self::RESULT_CODE   => 1,
                ],
                $data
            );
        }
            
        return $response;
    }
}
