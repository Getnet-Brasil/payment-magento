<?php
/**
 * Copyright © Getnet. All rights reserved.
 *
 * @author    Bruno Elisei <brunoelisei@o2ti.com>
 * See LICENSE for license details.
 */

declare(strict_types=1);

namespace Getnet\PaymentMagento\Model;

use Getnet\PaymentMagento\Api\CardIdManagementInterface;
use Getnet\PaymentMagento\Api\Data\CardIdInterface;
use Getnet\PaymentMagento\Gateway\Config\Config as ConfigBase;
use InvalidArgumentException;
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
 * Class Card Id Management - Get Details Card Id.
 */
class CardIdManagement implements CardIdManagementInterface
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
     * CardIdManagement constructor.
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
     * @param int             $cartId
     * @param CardIdInterface $cardId
     *
     * @throws CouldNotSaveException
     * @throws NoSuchEntityException
     *
     * @return array
     */
    public function getDetailsCardId(
        $cartId,
        CardIdInterface $cardId
    ) {
        $quote = $this->quoteRepository->getActive($cartId);
        if (!$quote->getItemsCount()) {
            throw new NoSuchEntityException(__('Cart %1 doesn\'t contain products', $cartId));
        }
        $data = [];

        $cardId = $cardId->getCardId();

        $storeId = $quote->getData(QuoteCartInterface::KEY_STORE_ID);

        $data['details'] = $this->getCardId($storeId, $cardId);

        return $data;
    }

    /**
     * Get Number Token.
     *
     * @param int    $storeId
     * @param string $cardId
     *
     * @return array
     */
    public function getCardId($storeId, $cardId)
    {
        $client = $this->httpClientFactory->create();
        $url = $this->configBase->getApiUrl($storeId);
        $apiBearer = $this->configBase->getMerchantGatewayOauth($storeId);

        try {
            $client->setUri($url.'v1/cards/'.$cardId);
            $client->setConfig(['maxredirects' => 0, 'timeout' => 45000]);
            $client->setHeaders('Authorization', 'Bearer '.$apiBearer);
            $client->setMethod(ZendClient::GET);

            $responseBody = $client->request()->getBody();
            $data = $this->json->unserialize($responseBody);
            $response = [
                'success' => 0,
            ];
            if (isset($data['number_token'])) {
                $response = [
                    'success' => 1,
                    'card'    => $data,
                ];
            }
            $this->logger->debug(
                [
                    'url'      => $url.'v1/cards/'.$cardId,
                    'request'  => $cardId,
                    'response' => $responseBody,
                ]
            );
        } catch (InvalidArgumentException $e) {
            $this->logger->debug(
                [
                    'url'      => $url.'v1/cards/'.$cardId,
                    'request'  => $cardId,
                    'response' => $responseBody,
                ]
            );
            // phpcs:ignore Magento2.Exceptions.DirectThrow
            throw new NoSuchEntityException('Invalid JSON was returned by the gateway');
        }

        return $response;
    }
}
