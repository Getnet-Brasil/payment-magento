<?php
/**
 * Copyright © Getnet. All rights reserved.
 *
 * @author    Bruno Elisei <brunoelisei@o2ti.com>
 * See LICENSE for license details.
 */

declare(strict_types=1);

namespace Getnet\PaymentMagento\Gateway\Http\Client\Boleto;

use Getnet\PaymentMagento\Gateway\Http\Api;
use Getnet\PaymentMagento\Gateway\Config\Config;
use Magento\Payment\Gateway\Http\ClientInterface;
use Magento\Payment\Gateway\Http\TransferInterface;

/**
 * Class Create Client - create order for payment by Boleto.
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
     * @var Config
     */
    protected $config;

    /**
     * @param Api       $api
     * @param Config    $config
     */
    public function __construct(
        Api $api,
        Config $config
    ) {
        $this->api = $api;
        $this->config = $config;
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

        $request = $this->config->prepareBody($request);

        $responseBody = $this->api->sendPostRequest(
            $transferObject,
            'v2/payments/boleto',
            $request,
        );

        $status = isset($responseBody['payment_id']) ? 1 : 0;
        $response = array_merge(
            [
                self::RESULT_CODE => $status,
                self::EXT_ORD_ID  => isset($responseBody['payment_id']) ? $responseBody['payment_id'] : null,
            ],
            $responseBody
        );

        return $response;
    }
}
