<?php
/**
 * Copyright © Getnet. All rights reserved.
 *
 * @author    Bruno Elisei <brunoelisei@o2ti.com>
 * See LICENSE for license details.
 */

namespace Getnet\PaymentMagento\Controller\Adminhtml\Order;

use Getnet\PaymentMagento\Api\Data\NumberTokenInterface;
use Getnet\PaymentMagento\Model\NumberTokenManagement;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Backend\Model\Session\Quote;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Serialize\Serializer\Json;

/**
 * Class Number Token - Generate Number Token.
 */
class NumberToken extends Action
{
    /**
     * Authorization level of a basic admin session.
     *
     * @see _isAllowed()
     */
    public const ADMIN_RESOURCE = 'Getnet_PaymentMagento::tokenize';

    /**
     * @var JsonFactory
     */
    protected $resultJsonFactory;

    /**
     * @var Json
     */
    protected $json;

    /**
     * @var Quote
     */
    protected $sessionManager;

    /**
     * @var NumberTokenManagement
     */
    protected $numberTokenModel;

    /**
     * @var NumberTokenInterface
     */
    protected $numberToken;

    /**
     * @param Context               $context
     * @param JsonFactory           $resultJsonFactory
     * @param Json                  $json
     * @param Quote                 $sessionManager
     * @param NumberTokenManagement $numberTokenModel
     * @param NumberTokenInterface  $numberToken
     */
    public function __construct(
        Context $context,
        JsonFactory $resultJsonFactory,
        Json $json,
        Quote $sessionManager,
        NumberTokenManagement $numberTokenModel,
        NumberTokenInterface $numberToken
    ) {
        $this->resultJsonFactory = $resultJsonFactory;
        $this->json = $json;
        $this->sessionManager = $sessionManager;
        $this->numberTokenModel = $numberTokenModel;
        $this->numberToken = $numberToken;
        parent::__construct($context);
    }

    /**
     * ACL.
     *
     * @return bool
     *
     * @SuppressWarnings(PHPMD.CamelCaseMethodName)
     */
    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed(self::ADMIN_RESOURCE);
    }

    /**
     * Execute.
     *
     * @return ResultInterface
     */
    public function execute()
    {
        $cardNumber = $this->getRequest()->getParam('cardNumber');
        $storeId = $this->getRequest()->getParam('storeId');

        /** @var $setCardNumber NumberTokenInterface */
        $setCardNumber = $this->numberToken->setCardNumber($cardNumber);

        try {
            /** @var $token NumberTokenManagement */
            $token = $this->numberTokenModel->generateNumberTokenForAdmin($storeId, $setCardNumber);

            return $this->resultJsonFactory->create()->setData([$token]);
        } catch (\Exception $e) {
            return $this->resultJsonFactory->create()->setData(
                [
                    'success' => false,
                    'error'   => true,
                ]
            );
        }
    }
}
