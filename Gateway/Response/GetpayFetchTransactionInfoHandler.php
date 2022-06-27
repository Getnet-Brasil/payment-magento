<?php
/**
 * Copyright © Getnet. All rights reserved.
 *
 * @author    Bruno Elisei <brunoelisei@o2ti.com>
 * See LICENSE for license details.
 */

namespace Getnet\PaymentMagento\Gateway\Response;

use Exception;
use InvalidArgumentException;
use Magento\Payment\Gateway\Data\PaymentDataObjectInterface;
use Magento\Payment\Gateway\Response\HandlerInterface;
use Magento\Sales\Model\Service\InvoiceService;
use Magento\Sales\Model\Service\OrderService;

/**
 * Class GetpayFetchTransactionInfoHandler - Looks for information about updating order status.
 */
class GetpayFetchTransactionInfoHandler implements HandlerInterface
{
    /**
     * @const string
     */
    public const RESULT_CODE = 'RESULT_CODE';

    /**
     * @const string
     */
    public const RESPONSE_STATUS = 'STATUS';

    /**
     * @var OrderService
     */
    protected $orderService;

    /**
     * @var InvoiceService
     */
    protected $invoiceService;

    /**
     * @param OrderService   $orderService
     * @param InvoiceService $invoiceService
     */
    public function __construct(
        OrderService $orderService,
        InvoiceService $invoiceService
    ) {
        $this->orderService = $orderService;
        $this->invoiceService = $invoiceService;
    }

    /**
     * Handles.
     *
     * @param array $handlingSubject
     * @param array $response
     *
     * @return void
     */
    public function handle(array $handlingSubject, array $response)
    {
        if (!isset($handlingSubject['payment'])
            || !$handlingSubject['payment'] instanceof PaymentDataObjectInterface
        ) {
            throw new InvalidArgumentException('Payment data object should be provided');
        }

        if ($response[self::RESULT_CODE] === 1) {
            $isAproved = false;
            $isDeny = true;

            $status = $response[self::RESPONSE_STATUS];

            if ($status) {
                $isAproved = true;
                $isDeny = false;
            }

            $paymentDO = $handlingSubject['payment'];

            $payment = $paymentDO->getPayment();

            $order = $payment->getOrder();

            if ($isAproved) {
                $this->processPay($order);
            }

            if ($isDeny) {
                $this->processCancel($order);
            }
        }
    }

    /**
     * Process Pay.
     *
     * @param OrderInterfaceFactory $order
     *
     * @throws Exception
     *
     * @return void
     */
    public function processPay($order)
    {
        $totalDue = $order->getTotalDue();
        $payment = $order->getPayment();

        $payment->setNotificationResult(true);
        $payment->registerCaptureNotification($totalDue);
        $payment->accept(true);

        try {
            $order->save();
            $this->communicateStatus($order, 'pay');
        } catch (Exception $exc) {
            // phpcs:ignore Magento2.Exceptions.DirectThrow
            throw new Exception($exc->getMessage());
        }
    }

    /**
     * Process Cancel.
     *
     * @param OrderInterfaceFactory $order
     *
     * @throws Exception
     *
     * @return void
     */
    public function processCancel($order)
    {
        $totalDue = $order->getTotalDue();
        $payment = $order->getPayment();

        $payment->setNotificationResult(true);
        $payment->registerVoidNotification($totalDue);
        $payment->deny(true);

        try {
            $order->save();
            $this->communicateStatus($order, 'cancel');
        } catch (Exception $exc) {
            // phpcs:ignore Magento2.Exceptions.DirectThrow
            throw new Exception($exc->getMessage());
        }
    }

    /**
     * Communicate status.
     *
     * @param OrderInterfaceFactory $order
     * @param string                $type
     *
     * @return void
     */
    public function communicateStatus($order, $type)
    {
        if ($type === 'pay') {
            $invoice = $order->getInvoiceCollection()->getFirstItem();
            $this->invoiceService->notify($invoice->getId());
        }

        if ($type === 'cancel') {
            $orderId = $order->getId();
            $comment = __('Order Canceled.');
            $history = $order->addStatusHistoryComment($comment, $order->getStatus());
            $history->setIsVisibleOnFront(true);
            $history->setIsCustomerNotified(true);
            $this->orderService->addComment($orderId, $history);
        }
    }
}
