<?php
/**
 * Copyright © Getnet. All rights reserved.
 *
 * @author    Bruno Elisei <brunoelisei@o2ti.com>
 * See LICENSE for license details.
 */

declare(strict_types=1);

namespace Getnet\PaymentMagento\Model\PixBiometric;

use Exception;
use Getnet\PaymentMagento\Gateway\Config\ConfigPix;
use Getnet\PaymentMagento\Gateway\Http\Client\Pix\BiometricStatusClient;
use Magento\Payment\Model\Method\Logger;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Email\Sender\InvoiceSender;

/**
 * Consolidate a Biometric Pix order with the status queried on the gateway.
 *
 * The webhook alone is not trusted: on the sandbox a PIX_APPROVED_TRANSACTIONS was
 * delivered for a payment that the gateway reports as DENIED. Every source (webhook,
 * payer return and cron) goes through this query before invoicing or canceling.
 */
class StatusUpdater
{
    public const STATUS_WAITING = 'WAITING';

    public const STATUS_APPROVED = 'APPROVED';

    public const STATUSES_CANCEL = ['DENIED', 'CANCELED', 'CANCELLED', 'ERROR'];

    /**
     * @var BiometricStatusClient
     */
    protected $statusClient;

    /**
     * @var OrderRepositoryInterface
     */
    protected $orderRepository;

    /**
     * @var InvoiceSender
     */
    protected $invoiceSender;

    /**
     * @var Logger
     */
    protected $logger;

    /**
     * @param BiometricStatusClient    $statusClient
     * @param OrderRepositoryInterface $orderRepository
     * @param InvoiceSender            $invoiceSender
     * @param Logger                   $logger
     */
    public function __construct(
        BiometricStatusClient $statusClient,
        OrderRepositoryInterface $orderRepository,
        InvoiceSender $invoiceSender,
        Logger $logger
    ) {
        $this->statusClient = $statusClient;
        $this->orderRepository = $orderRepository;
        $this->invoiceSender = $invoiceSender;
        $this->logger = $logger;
    }

    /**
     * Is the order a Pix paid through the biometric flow.
     *
     * @param Order $order
     *
     * @return bool
     */
    public function isBiometricOrder(Order $order): bool
    {
        $payment = $order->getPayment();

        return $payment
            && $payment->getMethod() === ConfigPix::METHOD
            && $payment->getAdditionalInformation(ConfigPix::MODE) === ConfigPix::MODE_BIOMETRIC;
    }

    /**
     * Query the gateway and apply the status to a pending order.
     *
     * @param Order $order
     *
     * @return string|null Gateway status, or null when it could not be queried
     */
    public function update(Order $order): ?string
    {
        if (!$this->isBiometricOrder($order)) {
            return null;
        }

        $paymentId = (string) ($order->getExtOrderId() ?: $order->getPayment()->getLastTransId());

        try {
            $status = $this->statusClient->fetchStatus($paymentId, $order->getStoreId());
        } catch (Exception $exc) {
            $this->logger->debug([
                'type'  => 'pix_biometric_status',
                'order' => $order->getIncrementId(),
                'error' => $exc->getMessage(),
            ]);

            return null;
        }

        if ($status === null || $order->getState() !== Order::STATE_NEW) {
            return $status;
        }

        if ($status === self::STATUS_APPROVED) {
            $this->pay($order);
        } elseif (in_array($status, self::STATUSES_CANCEL, true)) {
            $this->cancel($order);
        }

        return $status;
    }

    /**
     * Register the payment and invoice the order.
     *
     * @param Order $order
     *
     * @return void
     */
    protected function pay(Order $order): void
    {
        $payment = $order->getPayment();
        $payment->setNotificationResult(true);
        $payment->registerCaptureNotification($order->getTotalDue());
        $payment->accept(true);
        $this->orderRepository->save($order);

        $invoice = $order->getInvoiceCollection()->getFirstItem();

        if ($invoice && $invoice->getId() && !$invoice->getEmailSent()) {
            try {
                $this->invoiceSender->send($invoice);
            } catch (Exception $exc) {
                $this->logger->debug(['type' => 'pix_biometric_invoice_email', 'error' => $exc->getMessage()]);
            }
        }
    }

    /**
     * Cancel the order (payment denied, canceled or expired).
     *
     * @param Order $order
     *
     * @return void
     */
    protected function cancel(Order $order): void
    {
        $payment = $order->getPayment();
        $payment->setNotificationResult(true);
        $payment->registerVoidNotification($order->getTotalDue());
        $payment->deny(true);

        $history = $order->addStatusHistoryComment(
            __('Pix by biometrics was not authorized or expired. Order canceled.'),
            $order->getStatus()
        );
        $history->setIsVisibleOnFront(true);
        $this->orderRepository->save($order);
    }
}
