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

/**
 * Class Create Order Payment Cc Client - create authorization for payment by Cc.
 *
 * @SuppressWarnings(PHPCPD)
 */
class CreateOrderPaymentCcClient implements ClientInterface
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

        try {
            $client->setUri($url.'/v1/payments/credit');
            $client->setOptions(['maxredirects' => 0, 'timeout' => 45000]);

            $client->setHeaders(
                [
                    'Authorization'               => 'Bearer '.$apiBearer,
                    'Content-Type'                => 'application/json',
                    'x-transaction-channel-entry' => 'MG',
                ]
            );

            $client->setRawBody($this->json->serialize($request));
            $client->setMethod(Request::METHOD_POST);

            $responseBody = $client->send()->getBody();
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
                    'url'      => $url.'v1/payments/credit',
                    'request'  => $this->json->serialize($transferObject->getBody()),
                    'response' => $responseBody,
                ]
            );
        } catch (InvalidArgumentException $e) {
            $this->logger->debug(
                [
                    'exception' => $e->getMessage(),
                    'oauth'     => $apiBearer,
                    'url'       => $url.'v1/payments/credit',
                    'request'   => $this->json->serialize($transferObject->getBody()),
                    'response'  => $client->send()->getBody(),
                ]
            );
            // phpcs:ignore Magento2.Exceptions.DirectThrow
            throw new Exception('Invalid JSON was returned by the gateway');
        }

        return $response;
    }
}
