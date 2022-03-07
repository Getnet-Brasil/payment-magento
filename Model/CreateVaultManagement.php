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
use Getnet\PaymentMagento\Api\CreateVaultManagementInterface;
use Getnet\PaymentMagento\Gateway\Config\Config as ConfigBase;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\HTTP\ZendClient;
use Magento\Framework\HTTP\ZendClientFactory;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Payment\Gateway\ConfigInterface;
use Magento\Payment\Model\Method\Logger;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Api\Data\CartInterface as QuoteCartInterface;

/**
 * Class Create Vault Management - Generate number token by card number in API Cofre.
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class CreateVaultManagement implements CreateVaultManagementInterface
{
    /**
     * @var Logger
     */
    private $logger;

    /**
     * @var CartRepositoryInterface
     */
    protected $quoteRepository;

    /**
     * @var ConfigInterface
     */
    private $config;

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
     * CreateVaultManagement constructor.
     *
     * @param Logger                  $logger
     * @param CartRepositoryInterface $quoteRepository
     * @param ConfigInterface         $config
     * @param ConfigBase              $configBase
     * @param ZendClientFactory       $httpClientFactory
     * @param Json                    $json
     */
    public function __construct(
        Logger $logger,
        CartRepositoryInterface $quoteRepository,
        ConfigInterface $config,
        ConfigBase $configBase,
        ZendClientFactory $httpClientFactory,
        Json $json
    ) {
        $this->logger = $logger;
        $this->quoteRepository = $quoteRepository;
        $this->config = $config;
        $this->configBase = $configBase;
        $this->httpClientFactory = $httpClientFactory;
        $this->json = $json;
    }

    /**
     * Create Vault Card Id.
     *
     * @param int   $cartId
     * @param array $vaultData
     *
     * @throws CouldNotSaveException
     * @throws NoSuchEntityException
     *
     * @return array
     */
    public function createVault(
        $cartId,
        $vaultData
    ) {
        $token = [];
        $quote = $this->quoteRepository->getActive($cartId);
        if (!$quote->getItemsCount()) {
            throw new NoSuchEntityException(__('Cart %1 doesn\'t contain products', $cartId));
        }

        $storeId = $quote->getData(QuoteCartInterface::KEY_STORE_ID);

        $numberToken = $this->getVaultDetails($storeId, $vaultData);

        $token['tokenize'] = $numberToken;

        return $token;
    }

    /**
     * Get Vault Details.
     *
     * @param int   $storeId
     * @param array $vaultData
     *
     * @return array
     */
    public function getVaultDetails($storeId, $vaultData)
    {
        /** @var ZendClient $client */
        $client = $this->httpClientFactory->create();
        $request = ['card_number' => $vaultData['card_number']];
        $url = $this->configBase->getApiUrl($storeId);
        $apiBearer = $this->configBase->getMerchantGatewayOauth($storeId);

        try {
            $client->setUri($url.'/v1/tokens/card');
            $client->setConfig(['maxredirects' => 0, 'timeout' => 45000]);
            $client->setHeaders('Authorization', 'Bearer '.$apiBearer);
            $client->setRawData($this->json->serialize($request), 'application/json');
            $client->setMethod(ZendClient::POST);

            $responseBody = $client->request()->getBody();
            $data = $this->json->unserialize($responseBody);
            $response = [
                'success' => 0,
            ];
            if (isset($data['number_token'])) {

                $month = $vaultData['expiration_month'];
                if (strlen($month) === 1) {
                    $month = '0'.$month;
                }

                $saveCardData = [
                    'number_token'      => $data['number_token'],
                    'expiration_month'  => $month,
                    'expiration_year'   => $vaultData['expiration_year'],
                    'customer_id'       => $vaultData['customer_email'],
                    'cardholder_name'   => $vaultData['cardholder_name'],
                ];

                $client->setUri($url.'/v1/cards');
                $client->setConfig(['maxredirects' => 0, 'timeout' => 45000]);
                $client->setHeaders('Authorization', 'Bearer '.$apiBearer);
                $client->setRawData($this->json->serialize($saveCardData), 'application/json');
                $client->setMethod(ZendClient::POST);
    
                $responseBody = $client->request()->getBody();
                $data = $this->json->unserialize($responseBody);

                if (isset($data['card_id'])) {
                    $response = [
                        'success'      => 1,
                        'card_id'      => $data['card_id'],
                        'number_token' => $data['number_token'],
                    ];
                }
                $this->logger->debug(
                    [
                        'url'      => $url.'v1/cards',
                        'response' => $data,
                    ]
                );
            }
           
        } catch (InvalidArgumentException $e) {
            $this->logger->debug(
                [
                    'url'      => $url.'v1/cards',
                    'response' => $responseBody,
                ]
            );
            // phpcs:ignore Magento2.Exceptions.DirectThrow
            throw new Exception('Invalid JSON was returned by the gateway');
        }

        return $response;
    }
}
