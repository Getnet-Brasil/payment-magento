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
use Magento\Framework\HTTP\ZendClient;
use Magento\Framework\HTTP\ZendClientFactory;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Payment\Gateway\Http\ClientInterface;
use Magento\Payment\Gateway\Http\TransferInterface;
use Magento\Payment\Model\Method\Logger;

/**
 * Class Refund Client - Returns refund data.
 *
 * @SuppressWarnings(PHPCPD)
 */
class RefundClient implements ClientInterface
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
     * Amount block name.
     */
    public const CANCEL_AMOUNT = 'cancel_amount';

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
     * Response Pay Cancel Request Id - Block Name.
     */
    public const RESPONSE_CANCEL_CUSTOM_KEY = 'cancel_custom_key';

    /**
     * @var Logger
     */
    protected $logger;

    /**
     * @var ZendClientFactory
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
     * @param Logger            $logger
     * @param ZendClientFactory $httpClientFactory
     * @param Config            $config
     * @param Json              $json
     */
    public function __construct(
        Logger $logger,
        ZendClientFactory $httpClientFactory,
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
        /** @var ZendClient $client */
        $client = $this->httpClientFactory->create();
        $request = $transferObject->getBody();
        $storeId = $request[self::STORE_ID];
        $url = $this->config->getApiUrl($storeId);
        $apiBearer = $this->config->getMerchantGatewayOauth($storeId);
        $uri = $url.'v1/payments/cancel/request';
        $paymentId = $request[self::GETNET_PAYMENT_ID];

        if ($request[self::DAY_ZERO]) {
            $uri = $url.'v1/payments/credit/'.$paymentId.'/cancel';

            unset($request[self::CANCEL_AMOUNT]);
            unset($request[self::GETNET_PAYMENT_ID]);
        }

        unset($request[self::DAY_ZERO]);
        unset($request[self::STORE_ID]);

        try {
            $client->setUri($uri);
            $client->setConfig(['maxredirects' => 0, 'timeout' => 45000]);
            $client->setHeaders(
                [
                    'Authorization' => 'Bearer '.$apiBearer,
                ]
            );
            $client->setRawData($this->json->serialize($request), 'application/json');
            $client->setMethod(ZendClient::POST);

            $responseBody = $client->request()->getBody();
            $data = $this->json->unserialize($responseBody);
            $response = array_merge(
                [
                    self::RESULT_CODE  => 0,
                ],
                $data
            );
            if (isset($data[self::RESPONSE_CANCEL_REQUEST_ID])) {
                $response = array_merge(
                    [
                        self::RESULT_CODE                 => 1,
                        self::RESPONSE_CANCEL_REQUEST_ID  => $data[self::RESPONSE_CANCEL_REQUEST_ID],
                    ],
                    $data
                );
                if ($data[self::RESPONSE_STATUS] === self::RESPONSE_STATUS_DENIED) {
                    $response = array_merge(
                        [
                            self::RESULT_CODE                 => 0,
                            self::RESPONSE_CANCEL_REQUEST_ID  => $data[self::RESPONSE_CANCEL_REQUEST_ID],
                        ],
                        $data
                    );
                }
            }

            if (isset($data[self::RESPONSE_STATUS])) {
                if ($data[self::RESPONSE_STATUS] === 'CANCELED') {
                    $response = array_merge(
                        [
                            self::RESULT_CODE                 => 1,
                            self::RESPONSE_CANCEL_REQUEST_ID  => $paymentId.'-cancel',
                            self::RESPONSE_CANCEL_CUSTOM_KEY  => $paymentId.'-cancel',
                        ],
                        $data
                    );
                }
            }

            $this->logger->debug(
                [
                    'url'      => $uri,
                    'request'  => $this->json->serialize($request),
                    'response' => $this->json->serialize($response),
                ]
            );
        } catch (InvalidArgumentException $e) {
            $this->logger->debug(
                [
                    'url'       => $uri,
                    'request'   => $this->json->serialize($request),
                    'error'     => $e->getMessage(),
                    'msg'       => $client->request()->getBody(),
                ]
            );
            // phpcs:ignore Magento2.Exceptions.DirectThrow
            throw new Exception('Invalid JSON was returned by the gateway');
        }

        return $response;
    }
}
