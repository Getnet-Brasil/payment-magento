<?php
/**
 * Copyright © Getnet. All rights reserved.
 *
 * @author    Bruno Elisei <brunoelisei@o2ti.com>
 * See LICENSE for license details.
 */

namespace Getnet\PaymentMagento\Controller\Notification;

use Magento\Backend\App\Action\Context;
use Magento\Backend\Model\View\Result\ForwardFactory;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\App\CsrfAwareActionInterface;
use Magento\Framework\App\Request\InvalidRequestException;
use Magento\Framework\App\RequestInterface;

/**
 * Controler Notification Boleto - Notification of receivers for Boleto.
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class Boleto extends Action implements HttpGetActionInterface, HttpPostActionInterface, CsrfAwareActionInterface
{
    /**
     * @var ForwardFactory
     */
    protected $resultForwardFactory;

    /**
     * @param Context        $context
     * @param ForwardFactory $resultForwardFactory
     */
    public function __construct(
        Context $context,
        ForwardFactory $resultForwardFactory
    ) {
        parent::__construct($context);
        $this->resultForwardFactory = $resultForwardFactory;
    }

    /**
     * Catalog categories index action.
     *
     * @return \Magento\Backend\Model\View\Result\Forward
     */
    public function execute()
    {
        /** @var \Magento\Backend\Model\View\Result\Forward $resultForward */
        $resultForward = $this->resultForwardFactory->create();

        return $resultForward->forward('all');
    }

    /**
     * Create Csrf Validation Exception - webhook origin is validated by seller id.
     *
     * @param RequestInterface $request
     *
     * @return InvalidRequestException|null
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function createCsrfValidationException(RequestInterface $request): ?InvalidRequestException
    {
        return null;
    }

    /**
     * Validate For Csrf - external notification endpoint (V2 form post / Global JSON post).
     *
     * @param RequestInterface $request
     *
     * @return bool|null
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function validateForCsrf(RequestInterface $request): ?bool
    {
        return true;
    }
}
