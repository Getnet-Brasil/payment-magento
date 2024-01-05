<?php
/**
 * Copyright © Getnet. All rights reserved.
 *
 * @author    Bruno Elisei <brunoelisei@o2ti.com>
 * See LICENSE for license details.
 */

declare(strict_types=1);

namespace Getnet\PaymentMagento\Gateway\Http\Client\V1\Getpay\Operations;

use Getnet\PaymentMagento\Gateway\Http\Api;
use Magento\Framework\Exception\LocalizedException;
use Magento\Payment\Gateway\Http\ClientInterface;
use Magento\Payment\Gateway\Http\TransferInterface;
use Magento\Sales\Model\Order;

/**
 * Class Fetch Transaction Info Client - create authorization for fetch.
 *
 * @SuppressWarnings(PHPCPD)
 */
class FetchTransactionInfoClient implements ClientInterface
{
    /**
     * @var string
     */
    public const GETNET_PAYMENT_ID = 'payment_id';

    /**
     * Store Id - Block name.
     */
    public const STORE_ID = 'store_id';

    /**
     * @var string
     */
    public const GETNET_ORDER_ID = 'order_id';

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
    public const RESPONSE_EXPIRED = 'EXPIRED';

    /**
     * @const string
     */
    public const RESPONSE_SOLD_OUT = 'SOLD_OUT';

    /**
     * @const string
     */
    public const RESPONSE_SUCCESSFUL_ORDERS = 'successful_orders';

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
        $response = ['RESULT_CODE' => 0];
        $request = $transferObject->getBody();
        $getnetOrderId = $request[self::GETNET_ORDER_ID];

        if ($request[self::ORDER_STATE] !== Order::STATE_NEW) {
            // phpcs:ignore Magento2.Exceptions.DirectThrow
            throw new LocalizedException(__('Payment is not New.'));
        }

        $data = $this->api->sendGetRequest(
            $transferObject,
            'v1/payment-links/'.$getnetOrderId,
            $request,
        );

        if ($data[self::RESPONSE_STATUS] === self::RESPONSE_EXPIRED ||
            $data[self::RESPONSE_STATUS] === self::RESPONSE_SOLD_OUT) {
            $response = array_merge(
                [
                    'RESULT_CODE'       => 1,
                    'GETNET_ORDER_ID'   => $getnetOrderId,
                    'STATUS'            => $data[self::RESPONSE_SUCCESSFUL_ORDERS],
                ],
                $data
            );
        }

        return $response;
    }
}
