<?php
/**
 * Copyright © Getnet. All rights reserved.
 *
 * @author    Bruno Elisei <brunoelisei@o2ti.com>
 * See LICENSE for license details.
 */

declare(strict_types=1);

namespace Getnet\PaymentMagento\Controller\ApplePay;

use Getnet\PaymentMagento\Gateway\Config\ConfigApplePay;
use Getnet\PaymentMagento\Gateway\Http\Client\ApplePay\MerchantValidationClient;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Store\Model\StoreManagerInterface;
use Psr\Log\LoggerInterface;

/**
 * Class Validate - Apple Pay merchant validation (called by the checkout when the Apple Pay sheet opens).
 */
class Validate implements HttpPostActionInterface
{
    /**
     * @var RequestInterface
     */
    private $request;

    /**
     * @var JsonFactory
     */
    private $resultJsonFactory;

    /**
     * @var ConfigApplePay
     */
    private $config;

    /**
     * @var MerchantValidationClient
     */
    private $client;

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @param RequestInterface         $request
     * @param JsonFactory              $resultJsonFactory
     * @param ConfigApplePay           $config
     * @param MerchantValidationClient $client
     * @param StoreManagerInterface    $storeManager
     * @param LoggerInterface          $logger
     */
    public function __construct(
        RequestInterface $request,
        JsonFactory $resultJsonFactory,
        ConfigApplePay $config,
        MerchantValidationClient $client,
        StoreManagerInterface $storeManager,
        LoggerInterface $logger
    ) {
        $this->request = $request;
        $this->resultJsonFactory = $resultJsonFactory;
        $this->config = $config;
        $this->client = $client;
        $this->storeManager = $storeManager;
        $this->logger = $logger;
    }

    /**
     * Execute.
     *
     * @return ResultInterface
     */
    public function execute()
    {
        $result = $this->resultJsonFactory->create();
        $storeId = (int) $this->storeManager->getStore()->getId();

        if (!$this->config->isActive($storeId)) {
            return $result->setHttpResponseCode(404)->setData(['error' => true]);
        }

        try {
            $session = $this->client->validate((string) $this->request->getParam('validationURL'), $storeId);

            return $result->setData($session);
        } catch (LocalizedException $exception) {
            $this->logger->error('Getnet Apple Pay merchant validation: '.$exception->getMessage());
        }

        return $result->setHttpResponseCode(400)->setData(['error' => true]);
    }
}
