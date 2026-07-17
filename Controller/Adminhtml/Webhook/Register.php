<?php
/**
 * Copyright © Getnet. All rights reserved.
 *
 * @author    Bruno Elisei <brunoelisei@o2ti.com>
 * See LICENSE for license details.
 */

declare(strict_types=1);

namespace Getnet\PaymentMagento\Controller\Adminhtml\Webhook;

use Getnet\PaymentMagento\Model\WebhookManagement;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Exception\LocalizedException;

/**
 * Class Register - Registers Global API webhook subscriptions from the admin.
 */
class Register extends Action implements HttpPostActionInterface
{
    /**
     * Authorization level of a basic admin session.
     *
     * @see _isAllowed()
     */
    public const ADMIN_RESOURCE = 'Getnet_PaymentMagento::webhook';

    /**
     * @var JsonFactory
     */
    protected $resultJsonFactory;

    /**
     * @var WebhookManagement
     */
    protected $webhookManagement;

    /**
     * @param Context           $context
     * @param JsonFactory       $resultJsonFactory
     * @param WebhookManagement $webhookManagement
     */
    public function __construct(
        Context $context,
        JsonFactory $resultJsonFactory,
        WebhookManagement $webhookManagement
    ) {
        $this->resultJsonFactory = $resultJsonFactory;
        $this->webhookManagement = $webhookManagement;
        parent::__construct($context);
    }

    /**
     * Execute.
     *
     * @return ResultInterface
     */
    public function execute()
    {
        $resultJson = $this->resultJsonFactory->create();

        $storeId = $this->getRequest()->getParam('store');
        $storeId = ($storeId === null || $storeId === '') ? null : (int) $storeId;

        $callbackUrl = trim((string) $this->getRequest()->getParam('callback_url', ''));
        $callbackUrl = ($callbackUrl === '') ? null : $callbackUrl;

        try {
            $results = $this->webhookManagement->register($storeId, $callbackUrl);
        } catch (LocalizedException $exc) {
            return $resultJson->setData([
                'success' => false,
                'message' => $exc->getMessage(),
            ]);
        }

        $success = true;

        foreach ($results as $result) {
            $success = $success && $result['success'];
        }

        return $resultJson->setData([
            'success' => $success,
            'results' => $results,
        ]);
    }
}
