<?php
/**
 * Copyright © Getnet. All rights reserved.
 *
 * @author    Bruno Elisei <brunoelisei@o2ti.com>
 * See LICENSE for license details.
 */

declare(strict_types=1);

namespace Getnet\PaymentMagento\Gateway\Http;

use Exception;
use Getnet\PaymentMagento\Gateway\Config\Config;
use Getnet\PaymentMagento\Model\Cache\Type\GetnetCache;
use Getnet\PaymentMagento\Model\DataGetnetFactory;
use Laminas\Http\Request;
use Magento\Framework\App\Cache\Manager as CacheManager;
use Magento\Framework\App\Cache\TypeListInterface;
use Magento\Framework\App\CacheInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\HTTP\LaminasClient;
use Magento\Framework\HTTP\LaminasClientFactory;
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
     * @var LaminasClientFactory
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
     * @param Logger               $logger
     * @param LaminasClientFactory $httpClientFactory
     * @param Config               $config
     * @param Json                 $json
     * @param CacheInterface       $cache
     * @param TypeListInterface    $cacheTypeList
     * @param CacheManager         $cacheManager
     * @param DataGetnetFactory    $dataGetnet
     */
    public function __construct(
        Logger $logger,
        LaminasClientFactory $httpClientFactory,
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
     * Get Auth Cache Key, scoped by api type, environment and store.
     *
     * Prevents a cached token from one API/environment from being sent to another.
     *
     * @param int|null $storeId
     *
     * @return string
     */
    public function getAuthCacheKey($storeId = null): string
    {
        return sprintf(
            '%s_%s_%s_%s',
            GetnetCache::TYPE_IDENTIFIER,
            $this->config->getApiType($storeId),
            $this->config->getEnvironmentMode($storeId),
            (int) $storeId
        );
    }

    /**
     * Save Auth in Cache.
     *
     * @param string   $auth
     * @param int|null $storeId
     * @param int|null $lifetime
     *
     * @return void
     */
    public function saveAuthInCache($auth, $storeId = null, ?int $lifetime = null)
    {
        $cacheKey = $this->getAuthCacheKey($storeId);
        $cacheTag = GetnetCache::CACHE_TAG;
        $this->cache->save($auth, $cacheKey, [$cacheTag], $lifetime ?? GetnetCache::CACHE_LIFETIME);
    }

    /**
     * Has Auth in Cache.
     *
     * @param int|null $storeId
     *
     * @return bool|string
     */
    public function hasAuthInCache($storeId = null)
    {
        $cacheKey = $this->getAuthCacheKey($storeId);
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
            $authByCache = $this->hasAuthInCache($storeId);

            if ($authByCache) {
                return $authByCache;
            }
        }

        $responseBody = null;
        $uri = $this->config->getApiUrl($storeId);
        $authPath = $this->config->getAuthPath($storeId);
        $clientId = $this->config->getMerchantGatewayClientId($storeId);
        $clientSecret = $this->config->getMerchantGatewayClientSecret($storeId);
        $dataSend = [
            'grant_type' => 'client_credentials',
        ];

        if ($this->config->getApiType($storeId) === Config::API_TYPE_V2) {
            $dataSend['scope'] = 'oob';
        }

        $client = $this->httpClientFactory->create();
        $client->setUri($uri.$authPath);
        $client->setAuth($clientId, $clientSecret);
        $client->setOptions(['maxredirects' => 0, 'timeout' => 30]);
        $client->setHeaders(['content' => 'application/x-www-form-urlencoded']);
        $client->setParameterPost($dataSend);
        $client->setMethod(Request::METHOD_POST);

        try {
            $result = $client->send()->getBody();
            $responseBody = $this->json->unserialize($result);
            $this->collectLogger(
                $uri.$authPath,
                $client->getMethod(),
                [
                    'client_id'     => $clientId,
                    'client_secret' => $clientSecret,
                ],
                $dataSend,
                $responseBody,
            );

            if (isset($responseBody['access_token'])) {
                $lifetime = isset($responseBody['expires_in'])
                    ? max(60, (int) $responseBody['expires_in'] - 300)
                    : GetnetCache::CACHE_LIFETIME;
                $responseBody = $responseBody['access_token'];
                $this->saveAuthInCache($responseBody, $storeId, $lifetime);

                return $responseBody;
            }

            $responseBody = null;
        } catch (Exception $exc) {
            $response = $client->getResponse();
            $this->collectLogger(
                $uri.$authPath,
                $client->getMethod(),
                [
                    'client_id'     => $clientId,
                    'client_secret' => $clientSecret,
                ],
                $dataSend,
                $response ? $response->getBody() : '',
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
        $headers = $this->getDefaultHeaders($auth, $storeId);

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
        } catch (Exception $exc) {
            $response = $client->getResponse();
            $this->collectLogger(
                $uri,
                $client->getMethod(),
                $headers,
                $request,
                $response ? $response->getBody() : '',
                $exc->getMessage()
            );
            // phpcs:ignore Magento2.Exceptions.DirectThrow
            throw new LocalizedException(__('Invalid JSON was returned by the gateway'));
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
        $headers = $this->getDefaultHeaders($auth, $storeId);
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
        } catch (Exception $exc) {
            $this->collectLogger(
                $uri,
                $client->getMethod(),
                $headers,
                $request,
                $client->send()->getBody(),
                $exc->getMessage(),
            );
            // phpcs:ignore Magento2.Exceptions.DirectThrow
            throw new LocalizedException(__('Invalid JSON was returned by the gateway'));
        }

        return $data;
    }

    /**
     * Send Get Request with query params.
     *
     * @param TransferInterface|null $transferObject
     * @param string                 $path
     * @param array                  $request
     *
     * @return array
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function sendGetByParam($transferObject, $path, $request)
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
        $headers = $this->getDefaultHeaders($auth, $storeId);
        $uri .= $path;

        /** @var LaminasClient $client */
        $client = $this->httpClientFactory->create();

        try {
            $client->setUri($uri);
            $client->setHeaders($headers);
            $client->setMethod(Request::METHOD_GET);
            $client->setOptions(['maxredirects' => 0, 'timeout' => 30]);
            $client->setParameterGet($request);
            $responseBody = $client->send()->getBody();
            $data = $this->json->unserialize($responseBody);
            $this->collectLogger(
                $uri,
                $client->getMethod(),
                $headers,
                $request,
                $responseBody,
            );
        } catch (Exception $exc) {
            $response = $client->getResponse();
            $this->collectLogger(
                $uri,
                $client->getMethod(),
                $headers,
                $request,
                $response ? $response->getBody() : '',
                $exc->getMessage(),
            );
            // phpcs:ignore Magento2.Exceptions.DirectThrow
            throw new LocalizedException(__('Invalid JSON was returned by the gateway'));
        }

        return $data;
    }

    /**
     * Send Delete Request.
     *
     * The path already carries the resource identifier (e.g. the webhook event name).
     *
     * @param TransferInterface|null $transferObject
     * @param string                 $path
     * @param array                  $request
     *
     * @return array
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function sendDeleteRequest($transferObject, $path, $request)
    {
        $storeId = $request['store_id'] ?? null;
        unset($request['store_id']);
        $auth = $this->getAuth($storeId);

        if (!$auth) {
            // phpcs:ignore Magento2.Exceptions.DirectThrow
            throw new LocalizedException(__('Authentication Failed, please try again.'));
        }

        $data = [];
        $uri = $this->config->getApiUrl($storeId);
        $headers = $this->getDefaultHeaders($auth, $storeId);
        $uri .= $path;

        /** @var LaminasClient $client */
        $client = $this->httpClientFactory->create();

        try {
            $client->setUri($uri);
            $client->setHeaders($headers);
            $client->setMethod(Request::METHOD_DELETE);
            $client->setOptions(['maxredirects' => 0, 'timeout' => 30]);
            $responseBody = $client->send()->getBody();
            // A successful DELETE may return 204/empty body — treat it as success.
            $data = $responseBody === '' ? ['success' => true] : $this->json->unserialize($responseBody);
            $this->collectLogger(
                $uri,
                $client->getMethod(),
                $headers,
                $request,
                $responseBody,
            );
        } catch (Exception $exc) {
            $response = $client->getResponse();
            $this->collectLogger(
                $uri,
                $client->getMethod(),
                $headers,
                $request,
                $response ? $response->getBody() : '',
                $exc->getMessage(),
            );
            // phpcs:ignore Magento2.Exceptions.DirectThrow
            throw new LocalizedException(__('Invalid JSON was returned by the gateway'));
        }

        return $data;
    }

    /**
     * Get Default Headers by api type.
     *
     * The Global API resolves the seller from the oAuth token, so x-seller-id is V2-only.
     *
     * @param string   $auth
     * @param int|null $storeId
     *
     * @return array
     */
    public function getDefaultHeaders($auth, $storeId = null): array
    {
        $headers = [
            'Authorization'               => 'Bearer '.$auth,
            'Content-Type'                => 'application/json',
            'x-transaction-channel-entry' => 'MG',
        ];

        if ($this->config->getApiType($storeId) === Config::API_TYPE_V2) {
            $headers['x-seller-id'] = $this->config->getMerchantGatewaySellerId($storeId);
        }

        return $headers;
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

        try {
            $response = $this->json->unserialize($response);
        } catch (\InvalidArgumentException $exc) {
            // Gateway returned non-JSON content (e.g. WAF/edge error page)
            $response = ['raw_response' => mb_substr((string) $response, 0, 500)];
        }

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
