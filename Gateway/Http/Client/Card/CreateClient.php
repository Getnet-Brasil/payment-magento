<?php
/**
 * Copyright © Getnet. All rights reserved.
 *
 * @author    Bruno Elisei <brunoelisei@o2ti.com>
 * See LICENSE for license details.
 */

declare(strict_types=1);

namespace Getnet\PaymentMagento\Gateway\Http\Client\Card;

use Getnet\PaymentMagento\Gateway\Http\Api;
use Magento\Payment\Gateway\Http\ClientInterface;
use Magento\Payment\Gateway\Http\TransferInterface;

/**
 * Class Create Client - create authorization for payment by Cc.
 *
 * @SuppressWarnings(PHPCPD)
 */
class CreateClient implements ClientInterface
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

        $denied = 0;

        $responseBody = $this->api->sendPostRequest(
            $transferObject,
            'v2/payments',
            $request,
        );

        $status = isset($responseBody['payment_id']) ? 1 : 0;
        
        if (isset($responseBody['status'])) {
            if ($responseBody['status'] === 'DENIED') {
                $denied = 1;
            }
        }

        $response = array_merge(
            [
                self::RESULT_CODE => ($denied) ? 0 : $status,
                self::EXT_ORD_ID  => isset($responseBody['payment_id']) ? $responseBody['payment_id'] : null,
            ],
            $responseBody
        );

        return $response;
    }
}
