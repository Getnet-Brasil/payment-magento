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
 * Class Create Order Payment Getpay Client - create order for payment by Pix.
 *
 * @SuppressWarnings(PHPCPD)
 */
class CreateOrderPaymentGetpayClient implements ClientInterface
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
     * External Order Id - Block name.
     */
    public const EXT_ORD_ID = 'EXT_ORD_ID';

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

        $responseBody = $this->api->sendPostRequest(
            $transferObject,
            'v1/payment-links',
            $request,
        );

        $status = isset($responseBody['link_id']) ? 1 : 0;
        $response = array_merge(
            [
                self::RESULT_CODE => $status,
                self::EXT_ORD_ID  => $responseBody['link_id'] ? $responseBody['link_id'] : null,
            ],
            $responseBody
        );

        return $response;
    }
}
