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
use Magento\Sales\Model\Order;

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
        $url = $this->config->getApiUrl();
        $apiBearer = $this->config->getMerchantGatewayOauth();
        $getnetPaymentId = $request[self::GETNET_PAYMENT_ID];
        $response = ['RESULT_CODE' => 0];

        if ($request[self::ORDER_STATE] !== Order::STATE_NEW) {
            // phpcs:ignore Magento2.Exceptions.DirectThrow
            throw new InvalidArgumentException('Payment is not New.');
        }

        try {
            $client->setUri($url.'v1/payments/qrcode/'.$getnetPaymentId);
            $client->setConfig(['maxredirects' => 0, 'timeout' => 45000]);
            $client->setHeaders(
                [
                    'Authorization'               => 'Bearer '.$apiBearer,
                    'x-transaction-channel-entry' => 'MG',
                ]
            );
            $client->setMethod(ZendClient::GET);

            $responseBody = $client->request()->getBody();
            $data = $this->json->unserialize($responseBody);
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
        } catch (InvalidArgumentException $e) {
            // phpcs:ignore Magento2.Exceptions.DirectThrow
            throw new Exception('Invalid JSON was returned by the gateway');
        }
        $this->logger->debug(
            [
                'url'      => $url.'v1/payments/qrcode/'.$getnetPaymentId,
                'response' => $responseBody,
            ]
        );

        return $response;
    }
}
