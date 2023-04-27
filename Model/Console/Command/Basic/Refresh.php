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
use Magento\Framework\App\State;
use Laminas\Http\ClientFactory;
use Laminas\Http\Request;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Payment\Model\Method\Logger;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;

/**
 * Class Refresh Token.
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
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
     * @var State
     */
    protected $state;

    /**
     * @var GetnetConfig
     */
    protected $getnetConfig;

    /**
     * @var Config
     */
    protected $config;

    /**
     * @var StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @var Json
     */
    protected $json;

    /**
     * @var ClientFactory
     */
    protected $httpClientFactory;

    /**
     * @param TypeListInterface     $cacheTypeList
     * @param Pool                  $cacheFrontendPool
     * @param Logger                $logger
     * @param State                 $state
     * @param GetnetConfig          $getnetConfig
     * @param Config                $config
     * @param StoreManagerInterface $storeManager
     * @param Json                  $json
     * @param ClientFactory         $httpClientFactory
     */
    public function __construct(
        TypeListInterface $cacheTypeList,
        Pool $cacheFrontendPool,
        Logger $logger,
        State $state,
        GetnetConfig $getnetConfig,
        Config $config,
        StoreManagerInterface $storeManager,
        Json $json,
        ClientFactory $httpClientFactory
    ) {
        parent::__construct(
            $logger
        );
        $this->cacheTypeList = $cacheTypeList;
        $this->cacheFrontendPool = $cacheFrontendPool;
        $this->state = $state;
        $this->getnetConfig = $getnetConfig;
        $this->config = $config;
        $this->storeManager = $storeManager;
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
        $storeIds = $storeId ?: null;
        $this->writeln('Init Referesh Token');
        if (!$storeIds) {
            foreach ($this->storeManager->getStores() as $stores) {
                $storeId = (int) $stores->getId();
                $this->storeManager->setCurrentStore($stores);
                $webSiteId = (int) $stores->getWebsiteId();
                $this->writeln(__('For Store Id %1 Web Site Id %2', $storeId, $webSiteId));
                $this->createNewToken($storeId, $webSiteId);
            }
        }
        $this->writeln(__('Finished'));
    }

    /**
     * Create New Token.
     *
     * @param int $storeId
     * @param int $webSiteId
     *
     * @return void
     */
    protected function createNewToken(int $storeId = 0, int $webSiteId = 0)
    {
        $newToken = $this->getNewToken($storeId);
        if ($newToken['success']) {
            $token = $newToken['response'];
            if (isset($token['access_token'])) {
                $registryConfig = $this->setNewToken($token['access_token'], $storeId, $webSiteId);
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
     * @param int $storeId
     *
     * @return array
     */
    protected function getNewToken(int $storeId = 0): array
    {
        $uri = $this->getnetConfig->getApiUrl($storeId);
        $clientId = $this->getnetConfig->getMerchantGatewayClientId($storeId);
        $clientSecret = $this->getnetConfig->getMerchantGatewayClientSecret($storeId);
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
     * @param int    $webSiteId
     *
     * @return array
     */
    protected function setNewToken(string $token, int $storeId = 0, int $webSiteId = 0): array
    {
        $environment = $this->getnetConfig->getEnvironmentMode($storeId);
        $pathPattern = 'payment/getnet_paymentmagento/%s_%s';
        $pathConfigId = sprintf($pathPattern, 'access_token', $environment);

        try {
            $this->config->saveConfig(
                $pathConfigId,
                $token,
                ScopeInterface::SCOPE_WEBSITES,
                $webSiteId
            );
        } catch (Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }

        return ['success' => true];
    }
}
