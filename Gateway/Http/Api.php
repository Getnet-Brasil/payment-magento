<?php
/**
 * Copyright © Getnet. All rights reserved.
 *
 * @author    Bruno Elisei <brunoelisei@o2ti.com>
 * See LICENSE for license details.
 */

declare(strict_types=1);

namespace Getnet\PaymentMagento\Gateway\Http;

use Getnet\PaymentMagento\Gateway\Config\Config;
use Getnet\PaymentMagento\Model\Cache\Type\GetnetCache;
use Getnet\PaymentMagento\Model\DataGetnetFactory;
use Laminas\Http\ClientFactory;
use Laminas\Http\Request;
use Magento\Framework\App\Cache\Manager as CacheManager;
use Magento\Framework\App\Cache\TypeListInterface;
use Magento\Framework\App\CacheInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Payment\Gateway\Http\TransferInterface;
use Magento\Payment\Model\Method\Logger;

/**
 * Class Api - Connecting.
 *
 * @SuppressWarnings(PHPCPD)
 */
class Api
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
     * @var Json
     */
    protected $json;

    /**
     * @var CacheInterface
     */
    protected $cache;

    /**
     * @var TypeListInterface
     */
    protected $cacheTypeList;

    /**
     * @var CacheManager
     */
    protected $cacheManager;

    /**
     * @var DataGetnetFactory
     */
    protected $dataGetnet;

    /**
     * @param Logger            $logger
     * @param ClientFactory     $httpClientFactory
     * @param Config            $config
     * @param Json              $json
     * @param CacheInterface    $cache
     * @param TypeListInterface $cacheTypeList
     * @param CacheManager      $cacheManager
     * @param DataGetnetFactory $dataGetnet
     */
    public function __construct(
        Logger $logger,
        ClientFactory $httpClientFactory,
        Config $config,
        Json $json,
        CacheInterface $cache,
        TypeListInterface $cacheTypeList,
        CacheManager $cacheManager,
        DataGetnetFactory $dataGetnet
    ) {
        $this->config = $config;
        $this->httpClientFactory = $httpClientFactory;
        $this->logger = $logger;
        $this->json = $json;
        $this->cache = $cache;
        $this->cacheTypeList = $cacheTypeList;
        $this->cacheManager = $cacheManager;
        $this->dataGetnet = $dataGetnet;
    }

    /**
     * Save Auth in Cache.
     *
     * @param string $auth
     *
     * @return void
     */
    public function saveAuthInCache($auth)
    {
        $cacheKey = GetnetCache::TYPE_IDENTIFIER;
        $cacheTag = GetnetCache::CACHE_TAG;
        $this->cacheTypeList->cleanType($cacheKey);
        $this->cache->save($auth, $cacheKey, [$cacheTag], GetnetCache::CACHE_LIFETIME);
    }

    /**
     * Has Auth in Cache.
     *
     * @param int|null $storeId
     *
     * @return bool
     */
    public function hasAuthInCache()
    {
        $cacheKey = GetnetCache::TYPE_IDENTIFIER;
        $cacheExiste = $this->cache->load($cacheKey) ?: false;

        return $cacheExiste;
    }

    /**
     * Get Auth.
     *
     * @param int|null $storeId
     *
     * @return string
     */
    public function getAuth($storeId)
    {
        $useCache = $this->config->useAuthInCache($storeId);

        if ($useCache) {
            $authByCache = $this->hasAuthInCache();

            if ($authByCache) {
                return $authByCache;
            }
        }

        $responseBody = null;
        $uri = $this->config->getApiUrl($storeId);
        $clientId = $this->config->getMerchantGatewayClientId($storeId);
        $clientSecret = $this->config->getMerchantGatewayClientSecret($storeId);
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
                $uri.'auth/oauth/v2/token',
                $client->getMethod(),
                [
                    'client_id'     => $clientId,
                    'client_secret' => $clientSecret,
                ],
                $dataSend,
                $responseBody,
            );
            $responseBody = $responseBody['access_token'];
            $this->saveAuthInCache($responseBody);
        } catch (LocalizedException $exc) {
            $this->collectLogger(
                $uri.'auth/oauth/v2/token',
                $client->getMethod(),
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
     * @param TransferInterface $transferObject
     * @param string            $path
     * @param array             $request
     * @param string|null       $additional
     *
     * @return array
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function sendPostRequest($transferObject, $path, $request, $additional = null)
    {
        $storeId = $request['store_id'];
        unset($request['store_id']);
        $auth = $this->getAuth($storeId);

        if (!$auth) {
            // phpcs:ignore Magento2.Exceptions.DirectThrow
            throw new LocalizedException(__('Authentication Failed, please try again.'));
        }

        $data = [];
        $uri = $this->config->getApiUrl($storeId);
        $sellerId = $this->config->getMerchantGatewaySellerId($storeId);
        $headers = [
            'Authorization'               => 'Bearer '.$auth,
            'Content-Type'                => 'application/json',
            'x-transaction-channel-entry' => 'MG',
            'x-seller-id'                 => $sellerId,
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
            $client->setOptions(['maxredirects' => 0, 'timeout' => 30]);
            $client->setMethod(Request::METHOD_POST);
            $responseBody = $client->send()->getBody();
            $data = $this->json->unserialize($responseBody);
            $this->collectLogger(
                $uri,
                $client->getMethod(),
                $headers,
                $request,
                $responseBody,
            );
        } catch (LocalizedException $exc) {
            $this->collectLogger(
                $uri,
                $client->getMethod(),
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
     * Send Get Request.
     *
     * @param TransferInterface $transferObject
     * @param string            $path
     * @param array             $request
     *
     * @return array
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function sendGetRequest($transferObject, $path, $request)
    {
        $storeId = $request['store_id'];
        unset($request['store_id']);
        $auth = $this->getAuth($storeId);

        if (!$auth) {
            // phpcs:ignore Magento2.Exceptions.DirectThrow
            throw new LocalizedException(__('Authentication Failed, please try again.'));
        }

        $data = [];
        $uri = $this->config->getApiUrl($storeId);
        $headers = [
            'Authorization'               => 'Bearer '.$auth,
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
            $client->setOptions(['maxredirects' => 0, 'timeout' => 30]);
            $client->setRawBody($this->json->serialize($request));
            $responseBody = $client->send()->getBody();
            $data = $this->json->unserialize($responseBody);
            $this->collectLogger(
                $uri,
                $client->getMethod(),
                $headers,
                $request,
                $client->send()->getBody(),
            );
        } catch (LocalizedException $exc) {
            $this->collectLogger(
                $uri,
                $client->getMethod(),
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
     * @param string      $method
     * @param string      $headers
     * @param array       $payload
     * @param array       $response
     * @param string|null $message
     *
     * @return void
     */
    public function collectLogger(
        $uri,
        $method,
        $headers,
        $payload,
        $response,
        $message = null
    ) {
        if (is_array($response)) {
            $response = $this->json->serialize($response);
        }

        $protectedRequest = $this->config->getPrivateKeys();
        $env = $this->config->getEnvironmentMode();

        $response = $this->json->unserialize($response);

        if ($env === 'production') {
            $headers = $this->filterDebugData(
                $headers,
                $protectedRequest
            );

            $payload = $this->filterDebugData(
                $payload,
                $protectedRequest
            );

            $response = $this->filterDebugData(
                $response,
                $protectedRequest
            );
        }

        $dataGetnet = $this->dataGetnet->create();

        $dataGetnet->setUrl($uri);
        $dataGetnet->setHeader($this->json->serialize($headers));
        $dataGetnet->setPayload($this->json->serialize($payload));
        $dataGetnet->setResponse($this->json->serialize($response));
        $dataGetnet->setErrorMsg($message);
        $dataGetnet->save();

        $this->logger->debug(
            [
                'url'       => $uri,
                'method'    => $method,
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
