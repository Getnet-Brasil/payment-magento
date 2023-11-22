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
 * Class WalletFetchTransactionInfoClient - create authorization for fetch.
 *
 * @SuppressWarnings(PHPCPD)
 */
class WalletFetchTransactionInfoClient implements ClientInterface
{
    /**
     * @var string
     */
    public const GETNET_PAYMENT_ID = 'payment_id';

    /**
     * @var string
     */
    public const GETNET_ORDER_ID = 'order_id';

    /**
     * Store Id - Block name.
     */
    public const STORE_ID = 'store_id';

    /**
     * Order State - Block Name.
     */
    public const ORDER_STATE = 'state';

    /**
     * @const string
     */
    public const RESPONSE_STATUS = 'status';

    /**
     * @const string
     */
    public const RESPONSE_DENIED = 'DENIED';

    /**
     * @const string
     */
    public const RESPONSE_APPROVED = 'APPROVED';

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
        $response = ['RESULT_CODE' => 0];
        $getnetPaymentId = $request[self::GETNET_PAYMENT_ID];
        $path = 'v1/payments/qrcode/'.$getnetPaymentId;

        $data = $this->api->sendGetRequest(
            $transferObject,
            $path,
            $request,
        );

        $status = $data[self::RESPONSE_STATUS];
        if ($status === self::RESPONSE_DENIED || $status === self::RESPONSE_APPROVED) {
            $response = array_merge(
                [
                    'RESULT_CODE'       => 1,
                    'GETNET_ORDER_ID'   => $getnetPaymentId,
                    'STATUS'            => $status,
                ],
                $data
            );
        }

        return $response;
    }
}
