<?php
/**
 * Copyright © Getnet. All rights reserved.
 *
 * @author    Bruno Elisei <brunoelisei@o2ti.com>
 * See LICENSE for license details.
 */

declare(strict_types=1);

namespace Getnet\PaymentMagento\Controller\Pixbiometric;

use Getnet\PaymentMagento\Gateway\Config\ConfigPix;
use Getnet\PaymentMagento\Gateway\Response\Pix\BiometricTxnIdHandler;
use Getnet\PaymentMagento\Model\PixBiometric\StatusUpdater;
use Magento\Checkout\Model\Session;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\Controller\Result\Redirect as ResultRedirect;
use Magento\Framework\Controller\Result\RedirectFactory;
use Magento\Sales\Model\Order;

/**
 * Send the payer of the last placed order to the biometric journey.
 */
class Redirect implements HttpGetActionInterface
{
    /**
     * @var Session
     */
    protected $checkoutSession;

    /**
     * @var RedirectFactory
     */
    protected $redirectFactory;

    /**
     * @var ConfigPix
     */
    protected $configPix;

    /**
     * @var StatusUpdater
     */
    protected $statusUpdater;

    /**
     * @param Session         $checkoutSession
     * @param RedirectFactory $redirectFactory
     * @param ConfigPix       $configPix
     * @param StatusUpdater   $statusUpdater
     */
    public function __construct(
        Session $checkoutSession,
        RedirectFactory $redirectFactory,
        ConfigPix $configPix,
        StatusUpdater $statusUpdater
    ) {
        $this->checkoutSession = $checkoutSession;
        $this->redirectFactory = $redirectFactory;
        $this->configPix = $configPix;
        $this->statusUpdater = $statusUpdater;
    }

    /**
     * Execute.
     *
     * @return ResultRedirect
     */
    public function execute()
    {
        $result = $this->redirectFactory->create();
        $order = $this->checkoutSession->getLastRealOrder();

        if (!$order || !$order->getId()) {
            return $result->setPath('checkout/cart');
        }

        $jsrUrl = (string) $order->getPayment()->getAdditionalInformation(BiometricTxnIdHandler::INFO_JSR_URL);

        if ($this->statusUpdater->isBiometricOrder($order)
            && $order->getState() === Order::STATE_NEW
            && $jsrUrl !== ''
            && $this->configPix->isAllowedBiometricUrl($jsrUrl, $order->getStoreId())
        ) {
            return $result->setUrl($jsrUrl);
        }

        return $result->setPath('checkout/onepage/success');
    }
}
