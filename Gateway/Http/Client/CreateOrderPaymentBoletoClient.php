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
 * Class Create Order Payment Boleto Client - create order for payment by Boleto.
 *
 * @SuppressWarnings(PHPCPD)
 */
class CreateOrderPaymentBoletoClient implements ClientInterface
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
        unset($request[self::STORE_ID]);

        try {
            $client->setUri($url.'/v1/payments/boleto');
            $client->setConfig(['maxredirects' => 0, 'timeout' => 45000]);

            $client->setHeaders(
                [
                    'Authorization'               => 'Bearer '.$apiBearer,
                    'x-transaction-channel-entry' => 'MG',
                ]
            );

            $body = $this->config->prepareBody($request);
            $client->setRawData($body, 'application/json');
            $client->setMethod(ZendClient::POST);

            $responseBody = $client->request()->getBody();
            $data = $this->json->unserialize($responseBody);
            $response = array_merge(
                [
                    self::RESULT_CODE  => 0,
                ],
                $data
            );
            if (isset($data['payment_id'])) {
                $response = array_merge(
                    [
                        self::RESULT_CODE => 1,
                        self::EXT_ORD_ID  => $data['payment_id'],
                    ],
                    $data
                );
            }
            $this->logger->debug(
                [
                    'storeId'  => $storeId,
                    'url'      => $url.'v1/payments/boleto',
                    'auth'     => $apiBearer,
                    'request'  => $body,
                    'response' => $responseBody,
                ]
            );
        } catch (InvalidArgumentException $e) {
            $this->logger->debug(
                [
                    'storeId'   => $storeId,
                    'url'       => $url.'v1/payments/boleto',
                    'auth'      => $apiBearer,
                    'request'   => $body,
                    'response'  => $client->request()->getBody(),
                    'error'     => $e->getMessage(),
                ]
            );
            // phpcs:ignore Magento2.Exceptions.DirectThrow
            throw new Exception('Invalid JSON was returned by the gateway');
        }

        return $response;
    }
}
