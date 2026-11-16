<?php
/**
 * Copyright © Getnet. All rights reserved.
 *
 * @author    Bruno Elisei <brunoelisei@o2ti.com>
 * See LICENSE for license details.
 */

declare(strict_types=1);

namespace Getnet\PaymentMagento\Controller\Adminhtml\Pixbiometric;

use Getnet\PaymentMagento\Model\OpenFinanceManagement;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Exception\LocalizedException;

/**
 * Register the Pix by biometrics return URL (Open Finance merchant domain).
 */
class RegisterDomain extends Action implements HttpPostActionInterface
{
    public const ADMIN_RESOURCE = 'Getnet_PaymentMagento::pix_biometric';

    /**
     * @var JsonFactory
     */
    protected $resultJsonFactory;

    /**
     * @var OpenFinanceManagement
     */
    protected $openFinanceManagement;

    /**
     * @param Context               $context
     * @param JsonFactory           $resultJsonFactory
     * @param OpenFinanceManagement $openFinanceManagement
     */
    public function __construct(
        Context $context,
        JsonFactory $resultJsonFactory,
        OpenFinanceManagement $openFinanceManagement
    ) {
        $this->resultJsonFactory = $resultJsonFactory;
        $this->openFinanceManagement = $openFinanceManagement;
        parent::__construct($context);
    }

    /**
     * Execute.
     *
     * @return \Magento\Framework\Controller\Result\Json
     */
    public function execute()
    {
        $resultJson = $this->resultJsonFactory->create();
        $storeId = $this->getRequest()->getParam('store');
        $storeId = ($storeId === null || $storeId === '' || (int) $storeId === 0) ? null : (int) $storeId;

        try {
            $result = $this->openFinanceManagement->registerDomain($storeId);
        } catch (LocalizedException $exc) {
            $result = [
                'success' => false,
                'message' => $exc->getMessage(),
            ];
        }

        return $resultJson->setData($result);
    }
}
