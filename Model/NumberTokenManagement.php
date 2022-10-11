<?php
/**
 * Copyright © Getnet. All rights reserved.
 *
 * @author    Bruno Elisei <brunoelisei@o2ti.com>
 * See LICENSE for license details.
 */

declare(strict_types=1);

namespace Getnet\PaymentMagento\Model;

use Getnet\PaymentMagento\Api\Data\NumberTokenInterface;
use Getnet\PaymentMagento\Api\NumberTokenManagementInterface;
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
 * Class Number Token Management - Generate number token by card number.
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class NumberTokenManagement implements NumberTokenManagementInterface
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
     * NumberTokenManagement constructor.
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
     * Generate Number Token by Card Number.
     *
     * @param int                                                  $cartId
     * @param \Getnet\PaymentMagento\Api\Data\NumberTokenInterface $cardNumber
     *
     * @throws CouldNotSaveException
     * @throws NoSuchEntityException
     *
     * @return array
     */
    public function generateNumberToken(
        $cartId,
        NumberTokenInterface $cardNumber
    ) {
        $token = [];
        $quote = $this->quoteRepository->getActive($cartId);
        if (!$quote->getItemsCount()) {
            throw new NoSuchEntityException(__('Cart %1 doesn\'t contain products', $cartId));
        }

        $cardNumber = $cardNumber->getCardNumber();

        $storeId = $quote->getData(QuoteCartInterface::KEY_STORE_ID);

        $numberToken = $this->getNumberToken($storeId, $cardNumber);

        $token['tokenize'] = $numberToken;

        return $token;
    }

    /**
     * Generate Number Token by Card Number for Adminhtml.
     *
     * @param int                                                  $storeId
     * @param \Getnet\PaymentMagento\Api\Data\NumberTokenInterface $cardNumber
     *
     * @throws CouldNotSaveException
     * @throws NoSuchEntityException
     *
     * @return array
     */
    public function generateNumberTokenForAdmin(
        $storeId,
        NumberTokenInterface $cardNumber
    ) {
        $token = [];

        $cardNumber = $cardNumber->getCardNumber();

        $numberToken = $this->getNumberToken($storeId, $cardNumber);

        $token['tokenize'] = $numberToken;

        return $token;
    }

    /**
     * Get Number Token.
     *
     * @param int    $storeId
     * @param string $cardNumber
     *
     * @return array
     */
    public function getNumberToken($storeId, $cardNumber)
    {
        /** @var ZendClient $client */
        $client = $this->httpClientFactory->create();
        $request = ['card_number' => $cardNumber];
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
            if (!empty($data['number_token'])) {
                $response = [
                    'success'      => 1,
                    'number_token' => $data['number_token'],
                ];
            }
            $this->logger->debug(
                [
                    'url'      => $url.'v1/tokens/card',
                    'response' => $responseBody,
                ]
            );
            
            if (!$client->request()->isSuccessful()) {
                $response = [
                    'success' => 0,
                    'message' => [
                        'text' => __("Error creating payment. Please, contact the store owner or try again.")
                    ],
                ];
            }
        } catch (\InvalidArgumentException $e) {
            $this->logger->debug(
                [
                    'url'      => $url.'v1/tokens/card',
                    'response' => $responseBody,
                ]
            );
            // phpcs:ignore Magento2.Exceptions.DirectThrow
            throw new \Exception('Invalid JSON was returned by the gateway');
        }

        return $response;
    }
}
