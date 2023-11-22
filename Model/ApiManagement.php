<?php
/**
 * Copyright © Getnet. All rights reserved.
 *
 * @author    Bruno Elisei <brunoelisei@o2ti.com>
 * See LICENSE for license details.
 */

declare(strict_types=1);

namespace Getnet\PaymentMagento\Model;

use Getnet\PaymentMagento\Gateway\Config\Config as ConfigBase;
use Laminas\Http\ClientFactory;
use Laminas\Http\Request;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Payment\Gateway\Config\Config;
use Magento\Payment\Model\Method\Logger;

/**
 * Class Api - Connecting.
 *
 * @SuppressWarnings(PHPCPD)
 */
class ApiManagement
{
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
     * @var ConfigBase
     */
    protected $configBase;

    /**
     * @var Json
     */
    protected $json;

    /**
     * @param Logger        $logger
     * @param ClientFactory $httpClientFactory
     * @param Config        $config
     * @param ConfigBase    $configBase
     * @param Json          $json
     */
    public function __construct(
        Logger $logger,
        ClientFactory $httpClientFactory,
        Config $config,
        ConfigBase $configBase,
        Json $json
    ) {
        $this->logger = $logger;
        $this->httpClientFactory = $httpClientFactory;
        $this->config = $config;
        $this->configBase = $configBase;
        $this->json = $json;
    }

    /**
     * Get Auth.
     *
     * @param int|null $storeId
     *
     * @return array
     */
    public function getAuth($storeId)
    {
        $responseBody = [];
        $uri = $this->configBase->getApiUrl($storeId);
        $clientId = $this->configBase->getMerchantGatewayClientId($storeId);
        $clientSecret = $this->configBase->getMerchantGatewayClientSecret($storeId);
        $dataSend = [
            'scope'      => 'oob',
            'grant_type' => 'client_credentials',
        ];

        $client = $this->httpClientFactory->create();
        $client->setUri($uri.'auth/oauth/v2/token');
        $client->setAuth($clientId, $clientSecret);
        $client->setOptions(['maxredirects' => 0, 'timeout' => 30]);
        $client->setHeaders(['content' => 'application/x-www-form-urlencoded']);
        $client->setParameterPost($dataSend);
        $client->setMethod(Request::METHOD_POST);

        try {
            $result = $client->send()->getBody();
            $responseBody = $this->json->unserialize($result);
            $this->collectLogger(
                $uri,
                [
                    'client_id'     => $clientId,
                    'client_secret' => $clientSecret,
                ],
                $dataSend,
                $responseBody,
            );
        } catch (LocalizedException $exc) {
            $this->collectLogger(
                $uri,
                [
                    'client_id'     => $clientId,
                    'client_secret' => $clientSecret,
                ],
                $dataSend,
                $client->request()->getBody(),
                $exc->getMessage(),
            );
        }

        return $responseBody;
    }

    /**
     * Send Post Request.
     *
     * @param string      $path
     * @param array       $request
     * @param string|null $additional
     *
     * @return array
     */
    public function sendPostRequest($path, $request, $additional = null)
    {
        $storeId = $request['store_id'];
        unset($request['store_id']);
        $auth = $this->getAuth($storeId);

        if (!isset($auth['access_token'])) {
            // phpcs:ignore Magento2.Exceptions.DirectThrow
            throw new LocalizedException('Authentication Failed, please try again.');
        }

        $data = [];
        $uri = $this->configBase->getApiUrl($storeId);
        $headers = [
            'Authorization'               => 'Bearer '.$auth['access_token'],
            'Content-Type'                => 'application/json',
            'x-transaction-channel-entry' => 'MG',
        ];

        if ($additional) {
            $add = ['x-qrcode-expiration-time' => $request['pix_expiration']];
            $headers = array_merge($headers, $add);
            unset($request['pix_expiration']);
        }

        $uri .= $path;
        $payload = $this->json->serialize($request);
        /** @var LaminasClient $client */
        $client = $this->httpClientFactory->create();

        try {
            $client->setUri($uri);
            $client->setHeaders($headers);
            $client->setRawBody($payload);
            $client->setMethod(Request::METHOD_POST);
            $responseBody = $client->send()->getBody();
            $data = $this->json->unserialize($responseBody);
            $this->collectLogger(
                $uri,
                $headers,
                $request,
                $responseBody,
            );
        } catch (LocalizedException $exc) {
            $this->collectLogger(
                $uri,
                $headers,
                $request,
                $client->request()->getBody(),
                $exc->getMessage()
            );
            // phpcs:ignore Magento2.Exceptions.DirectThrow
            throw new LocalizedException('Invalid JSON was returned by the gateway');
        }

        return $data;
    }

    /**
     * Send Get By Param.
     *
     * @param string $path
     * @param array  $request
     *
     * @return array
     */
    public function sendGetByParam($path, $request)
    {
        $storeId = $request['store_id'];
        unset($request['store_id']);
        $auth = $this->getAuth($storeId);

        if (!isset($auth['access_token'])) {
            // phpcs:ignore Magento2.Exceptions.DirectThrow
            throw new LocalizedException('Authentication Failed, please try again.');
        }

        $data = [];
        $uri = $this->configBase->getApiUrl($storeId);
        $headers = [
            'Authorization'               => 'Bearer '.$auth['access_token'],
            'Content-Type'                => 'application/json',
            'x-transaction-channel-entry' => 'MG',
        ];
        $uri .= $path;

        /** @var LaminasClient $client */
        $client = $this->httpClientFactory->create();

        try {
            $client->setUri($uri);
            $client->setHeaders($headers);
            $client->setMethod(Request::METHOD_GET);
            $client->setParameterGet($request);
            $responseBody = $client->send()->getBody();
            $data = $this->json->unserialize($responseBody);
            $this->collectLogger(
                $uri,
                $headers,
                $request,
                $client->send()->getBody(),
            );
        } catch (LocalizedException $exc) {
            $this->collectLogger(
                $uri,
                $headers,
                $request,
                $client->send()->getBody(),
                $exc->getMessage(),
            );
            // phpcs:ignore Magento2.Exceptions.DirectThrow
            throw new LocalizedException('Invalid JSON was returned by the gateway');
        }

        return $data;
    }

    /**
     * Send Get Request.
     *
     * @param string $path
     * @param array  $request
     *
     * @return array
     */
    public function sendGetRequest($path, $request)
    {
        $storeId = $request['store_id'];
        unset($request['store_id']);
        $auth = $this->getAuth($storeId);

        if (!isset($auth['access_token'])) {
            // phpcs:ignore Magento2.Exceptions.DirectThrow
            throw new LocalizedException('Authentication Failed, please try again.');
        }

        $data = [];
        $uri = $this->configBase->getApiUrl($storeId);
        $headers = [
            'Authorization'               => 'Bearer '.$auth['access_token'],
            'Content-Type'                => 'application/json',
            'x-transaction-channel-entry' => 'MG',
        ];
        $uri .= $path;

        /** @var LaminasClient $client */
        $client = $this->httpClientFactory->create();

        try {
            $client->setUri($uri);
            $client->setHeaders($headers);
            $client->setMethod(Request::METHOD_GET);
            $client->setRawBody($this->json->serialize($request));
            $responseBody = $client->send()->getBody();
            $data = $this->json->unserialize($responseBody);
            $this->collectLogger(
                $uri,
                $headers,
                $request,
                $client->send()->getBody(),
            );
        } catch (LocalizedException $exc) {
            $this->collectLogger(
                $uri,
                $headers,
                $request,
                $client->send()->getBody(),
                $exc->getMessage(),
            );
            // phpcs:ignore Magento2.Exceptions.DirectThrow
            throw new LocalizedException('Invalid JSON was returned by the gateway');
        }

        return $data;
    }

    /**
     * Collect Logger.
     *
     * @param string      $uri
     * @param string      $headers
     * @param array       $payload
     * @param array       $response
     * @param string|null $message
     *
     * @return void
     */
    public function collectLogger(
        $uri,
        $headers,
        $payload,
        $response,
        $message = null
    ) {
        if (is_array($response)) {
            $response = $this->json->serialize($response);
        }

        $protectedRequest = [
            'card_number',
            'email',
            'tax_id',
            'number',
            'client_id',
            'client_secret',
            'access_token',
            'Authorization',
            'customer_id',
            'name',
            'last_name',

        ];

        $headers = $this->filterDebugData(
            $headers,
            $protectedRequest
        );

        $payload = $this->filterDebugData(
            $payload,
            $protectedRequest
        );

        $response = $this->filterDebugData(
            $this->json->unserialize($response),
            $protectedRequest
        );

        $this->logger->debug(
            [
                'url'       => $uri,
                'header'    => $this->json->serialize($headers),
                'payload'   => $this->json->serialize($payload),
                'response'  => $this->json->serialize($response),
                'error_msg' => $message,
            ]
        );
    }

    /**
     * Recursive filter data by private conventions.
     *
     * @param array $debugData
     * @param array $debugDataKeys
     *
     * @return array
     */
    protected function filterDebugData(array $debugData, array $debugDataKeys)
    {
        $debugDataKeys = array_map('strtolower', $debugDataKeys);

        foreach (array_keys($debugData) as $key) {
            if (in_array(strtolower((string) $key), $debugDataKeys)) {
                $debugData[$key] = '*** protected ***';
            } elseif (is_array($debugData[$key])) {
                $debugData[$key] = $this->filterDebugData($debugData[$key], $debugDataKeys);
            }
        }

        return $debugData;
    }
}
