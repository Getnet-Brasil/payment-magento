<?php
/**
 * Copyright © Getnet. All rights reserved.
 *
 * @author    Bruno Elisei <brunoelisei@o2ti.com>
 * See LICENSE for license details.
 */

declare(strict_types=1);

namespace Getnet\PaymentMagento\Model;

use Exception;
use Getnet\PaymentMagento\Gateway\Config\Config as ConfigBase;
use Magento\Framework\HTTP\ZendClient;
use Magento\Framework\HTTP\ZendClientFactory;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Payment\Model\Method\Logger;

/**
 * Class Consult Refund Management - refund data.
 */
class ConsultRefundManagement
{
    /**
     * @var Logger
     */
    private $logger;

    /**
     * @var ConfigBase
     */
    private $configBase;

    /**
     * @var ZendClientFactory
     */
    private $httpClientFactory;

    /**
     * @var Json
     */
    private $json;

    /**
     * NumberTokenManagement constructor.
     *
     * @param Logger            $logger
     * @param ConfigBase        $configBase
     * @param ZendClientFactory $httpClientFactory
     * @param Json              $json
     */
    public function __construct(
        Logger $logger,
        ConfigBase $configBase,
        ZendClientFactory $httpClientFactory,
        Json $json
    ) {
        $this->logger = $logger;
        $this->configBase = $configBase;
        $this->httpClientFactory = $httpClientFactory;
        $this->json = $json;
    }

    /**
     * Get Refund Data.
     *
     * @param int    $storeId
     * @param string $transactionId
     *
     * @return array
     */
    public function getRefundData($storeId, $transactionId)
    {
        /** @var ZendClient $client */
        $client = $this->httpClientFactory->create();
        $request = ['cancel_custom_key' => $transactionId];
        $url = $this->configBase->getApiUrl($storeId);
        $apiBearer = $this->configBase->getMerchantGatewayOauth($storeId);

        try {
            $client->setUri($url.'v1/payments/cancel/request?cancel_custom_key='.$transactionId);
            $client->setConfig(['maxredirects' => 0, 'timeout' => 45000]);
            $client->setHeaders([
                'Authorization' => 'Bearer '.$apiBearer,
                'Content-Type'  => 'application/json',
            ]);
            $client->setMethod(ZendClient::GET);

            $responseBody = $client->request()->getBody();
            $data = $this->json->unserialize($responseBody);
            $response = [];
            if (!empty($data['status_processing_cancel_code'])) {
                $response = [
                    'status_processing_cancel_code'     => $data['status_processing_cancel_code'],
                    'status_processing_cancel_message'  => $data['status_processing_cancel_message'],
                ];
            }
            $this->logger->debug(
                [
                    'baa'      => $apiBearer,
                    'file'     => 'ConsultRefundManagement',
                    'url'      => $url.'v1/payments/cancel/request',
                    'request'  => $request,
                    'response' => $responseBody,
                ]
            );
        } catch (\InvalidArgumentException $e) {
            $this->logger->debug(
                [
                    'url'      => $url.'v1/payments/cancel/request',
                    'request'  => $request,
                ]
            );
            // phpcs:ignore Magento2.Exceptions.DirectThrow
            throw new Exception('Invalid JSON was returned by the gateway');
        }

        return $response;
    }
}
