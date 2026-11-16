<?php
/**
 * Copyright © Getnet. All rights reserved.
 *
 * @author    Bruno Elisei <brunoelisei@o2ti.com>
 * See LICENSE for license details.
 */

declare(strict_types=1);

namespace Getnet\PaymentMagento\Controller\Pixbiometric;

use Getnet\PaymentMagento\Gateway\Response\Pix\BiometricTxnIdHandler;
use Getnet\PaymentMagento\Model\PixBiometric\StatusUpdater;
use Magento\Checkout\Model\Session;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\Result\Redirect as ResultRedirect;
use Magento\Framework\Controller\Result\RedirectFactory;
use Magento\Framework\Message\ManagerInterface;

/**
 * Landing route of the biometric journey (redirect_uri registered on Open Finance).
 *
 * The redirect_uri is per seller, so the order comes from the checkout session and is
 * matched against the paymentLinkId of the query string. The return never invoices by
 * itself: the status is always confirmed on the gateway.
 */
class ReturnAction implements HttpGetActionInterface
{
    /**
     * @var RequestInterface
     */
    protected $request;

    /**
     * @var Session
     */
    protected $checkoutSession;

    /**
     * @var RedirectFactory
     */
    protected $redirectFactory;

    /**
     * @var ManagerInterface
     */
    protected $messageManager;

    /**
     * @var StatusUpdater
     */
    protected $statusUpdater;

    /**
     * @param RequestInterface $request
     * @param Session          $checkoutSession
     * @param RedirectFactory  $redirectFactory
     * @param ManagerInterface $messageManager
     * @param StatusUpdater    $statusUpdater
     */
    public function __construct(
        RequestInterface $request,
        Session $checkoutSession,
        RedirectFactory $redirectFactory,
        ManagerInterface $messageManager,
        StatusUpdater $statusUpdater
    ) {
        $this->request = $request;
        $this->checkoutSession = $checkoutSession;
        $this->redirectFactory = $redirectFactory;
        $this->messageManager = $messageManager;
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

        if (!$order || !$order->getId() || !$this->statusUpdater->isBiometricOrder($order)) {
            // Payer came back on another browser/device: the confirmation arrives by e-mail
            $this->messageManager->addNoticeMessage(
                __('We received your return. The payment confirmation will be sent by e-mail.')
            );

            return $result->setPath('/');
        }

        $linkId = (string) $this->request->getParam('paymentLinkId', '');
        $expectedLinkId = (string) $order->getPayment()
            ->getAdditionalInformation(BiometricTxnIdHandler::INFO_PAYMENT_LINK_ID);

        if ($linkId === '' || $linkId === $expectedLinkId) {
            $status = $this->statusUpdater->update($order);
            $this->addStatusMessage($status);
        }

        return $result->setPath('checkout/onepage/success');
    }

    /**
     * Inform the payer about the payment status.
     *
     * @param string|null $status
     *
     * @return void
     */
    protected function addStatusMessage(?string $status): void
    {
        if ($status === StatusUpdater::STATUS_APPROVED) {
            $this->messageManager->addSuccessMessage(__('Your Pix payment was confirmed.'));

            return;
        }

        if (in_array($status, StatusUpdater::STATUSES_CANCEL, true)) {
            $this->messageManager->addErrorMessage(
                __('Your Pix by biometrics was not authorized. The order was canceled.')
            );

            return;
        }

        $this->messageManager->addNoticeMessage(
            __('We are waiting for the confirmation of your Pix. You will be notified by e-mail.')
        );
    }
}
