<?php
/**
 * Copyright © Getnet. All rights reserved.
 *
 * @author    Bruno Elisei <brunoelisei@o2ti.com>
 * See LICENSE for license details.
 */

declare(strict_types=1);

namespace Getnet\PaymentMagento\Gateway\Http\Client;

use Exception;
use Getnet\PaymentMagento\Gateway\Config\Config;
use InvalidArgumentException;
use Laminas\Http\ClientFactory;
use Laminas\Http\Request;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Payment\Gateway\Http\ClientInterface;
use Magento\Payment\Gateway\Http\TransferInterface;
use Magento\Payment\Model\Method\Logger;
use Magento\Sales\Model\Order;

/**
 * Class GetpayFetchTransactionInfoClient - create authorization for fetch.
 *
 * @SuppressWarnings(PHPCPD)
 */
class GetpayFetchTransactionInfoClient implements ClientInterface
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
     * @var Logger
     */
    protected $logger;

    /**
     * @var ClientFactory
     */
    protected $httpClientFactory;

    /**
     * @var Config
     */
    protected $config;

    /**
     * @var Json
     */
    protected $json;

    /**
     * @param Logger        $logger
     * @param ClientFactory $httpClientFactory
     * @param Config        $config
     * @param Json          $json
     */
    public function __construct(
        Logger $logger,
        ClientFactory $httpClientFactory,
        Config $config,
        Json $json
    ) {
        $this->config = $config;
        $this->httpClientFactory = $httpClientFactory;
        $this->logger = $logger;
        $this->json = $json;
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
        /** @var LaminasClient $client */
        $client = $this->httpClientFactory->create();
        $request = $transferObject->getBody();
        $storeId = $request[self::STORE_ID];
        $url = $this->config->getApiUrl($storeId);
        $apiBearer = $this->config->getMerchantGatewayOauth($storeId);
        unset($request[self::STORE_ID]);
        $getnetOrderId = $request[self::GETNET_ORDER_ID];
        $response = ['RESULT_CODE' => 0];

        if ($request[self::ORDER_STATE] !== Order::STATE_NEW) {
            // phpcs:ignore Magento2.Exceptions.DirectThrow
            throw new InvalidArgumentException('Payment is not New.');
        }

        try {
            $client->setUri($url.'v1/payment-links/'.$getnetOrderId);
            $client->setConfig(['maxredirects' => 0, 'timeout' => 45000]);
            $client->setHeaders(
                [
                    'Authorization'               => 'Bearer '.$apiBearer,
                    'x-transaction-channel-entry' => 'MG',
                ]
            );
            $client->setMethod(Request::METHOD_GET);

            $responseBody = $client->send()->getBody();
            $data = $this->json->unserialize($responseBody);

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
        } catch (InvalidArgumentException $e) {
            // phpcs:ignore Magento2.Exceptions.DirectThrow
            throw new Exception('Invalid JSON was returned by the gateway');
        }
        $this->logger->debug(
            [
                'url'      => $url.'v1/payment-links/'.$getnetOrderId,
                'response' => $responseBody,
            ]
        );

        return $response;
    }
}
