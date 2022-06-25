<?php
/**
 * Copyright © Getnet. All rights reserved.
 *
 * @author    Bruno Elisei <brunoelisei@o2ti.com>
 * See LICENSE for license details.
 */

declare(strict_types=1);

namespace Getnet\PaymentMagento\Model\Console\Command\Basic;

use Exception;
use Getnet\PaymentMagento\Gateway\Config\Config as GetnetConfig;
use Getnet\PaymentMagento\Model\Console\Command\AbstractModel;
use Magento\Config\Model\ResourceModel\Config;
use Magento\Framework\App\Cache\Frontend\Pool;
use Magento\Framework\App\Cache\TypeListInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\State;
use Magento\Framework\HTTP\ZendClient;
use Magento\Framework\HTTP\ZendClientFactory;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Payment\Model\Method\Logger;

/**
 * Class Refresh Token.
 */
class Refresh extends AbstractModel
{
    /**
     * @var TypeListInterface
     */
    protected $cacheTypeList;

    /**
     * @var Pool
     */
    protected $cacheFrontendPool;

    /**
     * @var ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * @var State
     */
    private $state;

    /**
     * @var GetnetConfig
     */
    private $getnetConfig;

    /**
     * @var Config
     */
    private $config;

    /**
     * @var Json
     */
    private $json;

    /**
     * @var ZendClientFactory
     */
    private $httpClientFactory;

    /**
     * @param TypeListInterface    $cacheTypeList
     * @param Pool                 $cacheFrontendPool
     * @param Logger               $logger
     * @param ScopeConfigInterface $scopeConfig
     * @param State                $state
     * @param GetnetConfig         $getnetConfig
     * @param Config               $config
     * @param Json                 $json
     * @param ZendClientFactory    $httpClientFactory
     */
    public function __construct(
        TypeListInterface $cacheTypeList,
        Pool $cacheFrontendPool,
        Logger $logger,
        ScopeConfigInterface $scopeConfig,
        State $state,
        GetnetConfig $getnetConfig,
        Config $config,
        Json $json,
        ZendClientFactory $httpClientFactory
    ) {
        parent::__construct(
            $logger
        );
        $this->cacheTypeList = $cacheTypeList;
        $this->cacheFrontendPool = $cacheFrontendPool;
        $this->state = $state;
        $this->scopeConfig = $scopeConfig;
        $this->getnetConfig = $getnetConfig;
        $this->config = $config;
        $this->json = $json;
        $this->httpClientFactory = $httpClientFactory;
    }

    /**
     * Command Preference.
     *
     * @param int|null $storeId
     *
     * @return void
     */
    public function newToken($storeId = null)
    {
        $storeId = $storeId ?: 0;
        $this->writeln('Init Referesh Token');
        $this->createNewToken($storeId);
        $this->writeln(__('Finished'));
    }

    /**
     * Create New Token.
     *
     * @param int $storeId
     *
     * @return void
     */
    private function createNewToken($storeId)
    {
        $newToken = $this->getNewToken();
        if ($newToken['success']) {
            $token = $newToken['response'];
            if (isset($token['access_token'])) {
                $registryConfig = $this->setNewToken($token['access_token'], $storeId);
                if ($registryConfig['success']) {
                    $this->cacheTypeList->cleanType('config');

                    // phpcs:ignore Generic.Files.LineLength
                    $this->writeln('<info>'.__('Token Refresh Successfully.').'</info>');

                    return;
                }
                // phpcs:ignore Generic.Files.LineLength
                $this->writeln('<error>'.__('Error saving information in database: %1', $registryConfig['error']).'</error>');
            }
            // phpcs:ignore Generic.Files.LineLength
            $this->writeln('<error>'.__('Refresh Token Error: %1', $token['error_description']).'</error>');

            return;
        }
        $this->writeln('<error>'.__('Token update request error: %1', $newToken['error']).'<error>');
    }

    /**
     * Get New Token.
     *
     * @return array
     */
    private function getNewToken(): array
    {
        $uri = $this->getnetConfig->getApiUrl();
        $clientId = $this->getnetConfig->getMerchantGatewayClientId();
        $clientSecret = $this->getnetConfig->getMerchantGatewayClientSecret();
        $dataSend = [
            'scope'      => 'oob',
            'grant_type' => 'client_credentials',
        ];
        $client = $this->httpClientFactory->create();
        $client->setUri($uri.'auth/oauth/v2/token');
        $client->setAuth($clientId, $clientSecret);
        $client->setConfig(['maxredirects' => 0, 'timeout' => 30]);
        $client->setHeaders(['content' => 'application/x-www-form-urlencoded']);
        $client->setParameterPost($dataSend);
        $client->setMethod(ZendClient::POST);

        try {
            $result = $client->request()->getBody();
            $response = $this->json->unserialize($result);
            $this->logger->debug(['response' => $response]);

            return [
                'success'    => true,
                'response'   => $response,
            ];
        } catch (Exception $e) {
            $this->logger->debug(['error' => $e->getMessage()]);

            return ['success' => false, 'error' =>  $e->getMessage()];
        }
    }

    /**
     * Set New Token.
     *
     * @param string $token
     * @param int    $storeId
     *
     * @return array
     */
    private function setNewToken(string $token, int $storeId): array
    {
        $environment = $this->getnetConfig->getEnvironmentMode();
        $pathPattern = 'payment/getnet_paymentmagento/%s_%s';
        $pathConfigId = sprintf($pathPattern, 'access_token', $environment);

        try {
            $this->config->saveConfig(
                $pathConfigId,
                $token,
                'default',
                $storeId
            );
        } catch (Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }

        return ['success' => true];
    }
}
