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
 * Class Two Cc Cancel Payment Client - Returns authorization to denied payment.
 *
 * @SuppressWarnings(PHPCPD)
 */
class TwoCcDenyPaymentClient implements ClientInterface
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
        $context = [];
        $url = $this->config->getApiUrl($storeId);
        $apiBearer = $this->config->getMerchantGatewayOauth($storeId);
        $paymentId = $request['payment_id'];
        $uri = $url.'v1/payments/combined/cancel/request';
        if ($request[self::DAY_ZERO]) {
            $uri = $url.'v1/payments/combined/cancel';
        }

        unset($request['payment_id']);
        unset($request[self::CANCEL_AMOUNT]);
        unset($request[self::DAY_ZERO]);
        unset($request[self::STORE_ID]);

        try {
            $client->setUri($uri);
            $client->setConfig(['maxredirects' => 0, 'timeout' => 45000]);
            $client->setHeaders(
                [
                    'Authorization'               => 'Bearer '.$apiBearer,
                    'Content-Type'                => 'application/json',
                    'x-transaction-channel-entry' => 'MG',
                ]
            );
            $client->setRawData($this->json->serialize($request));
            $client->setMethod(RequZendClient::POST);

            $responseBody = $client->request()->getBody();
            $data = $this->json->unserialize($responseBody);
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
                    'exception' => $e->getMessage(),
                    'url'       => $uri,
                    'request'   => $this->json->serialize($request),
                    'response'  => $client->send()->getBody(),
                ]
            );
            // phpcs:ignore Magento2.Exceptions.DirectThrow
            throw new Exception('Invalid JSON was returned by the gateway');
        }

        return $response;
    }
}
