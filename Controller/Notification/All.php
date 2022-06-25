<?php
/**
 * Copyright © Getnet. All rights reserved.
 *
 * @author    Bruno Elisei <brunoelisei@o2ti.com>
 * See LICENSE for license details.
 */

namespace Getnet\PaymentMagento\Controller\Notification;

use Exception;
use Getnet\PaymentMagento\Gateway\Config\Config;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\DataObjectFactory;
use Magento\Framework\View\Result\PageFactory;
use Magento\Payment\Model\Method\Logger;
use Magento\Sales\Api\Data\OrderInterfaceFactory;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Service\InvoiceService;
use Magento\Sales\Model\Service\OrderService;
use Magento\Store\Model\StoreManagerInterface;

/**
 * Controler Notification All - Notification of receivers for All Methods.
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class All extends Action
{
    /**
     * @const string
     */
    public const APPROVED_PAID = 'APPROVED';

    /**
     * @const string
     */
    public const ACCEPT_PAID = 'PAID';

    /**
     * @const string
     */
    public const ACCEPT_PAID_ALTERNATIVE = 'AUTHORIZED';

    /**
     * @const string
     */
    public const CANCELED_PAID = 'CANCELED';

    /**
     * @const string
     */
    public const DENNY_PAID = 'DENIED';

    /**
     * @const string
     */
    public const ERROR = 'ERROR';

    /**
     * @var Logger
     */
    protected $logger;

    /**
     * @var OrderInterfaceFactory
     */
    protected $orderFactory;

    /**
     * @var PageFactory
     */
    protected $pageFactory;

    /**
     * @var StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @var DataObjectFactory
     */
    protected $dataObjectFactory;

    /**
     * @var JsonFactory
     */
    protected $resultJsonFactory;

    /**
     * @var Config
     */
    protected $config;

    /**
     * @var OrderService
     */
    protected $orderService;

    /**
     * @var InvoiceService
     */
    protected $invoiceService;

    /**
     * @param Context               $context
     * @param Logger                $logger
     * @param OrderInterfaceFactory $orderFactory
     * @param PageFactory           $pageFactory
     * @param StoreManagerInterface $storeManager
     * @param DataObjectFactory     $dataObjectFactory
     * @param JsonFactory           $resultJsonFactory
     * @param Config                $config
     * @param OrderService          $orderService
     * @param InvoiceService        $invoiceService
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     */
    public function __construct(
        Context $context,
        Logger $logger,
        OrderInterfaceFactory $orderFactory,
        PageFactory $pageFactory,
        StoreManagerInterface $storeManager,
        DataObjectFactory $dataObjectFactory,
        JsonFactory $resultJsonFactory,
        Config $config,
        OrderService $orderService,
        InvoiceService $invoiceService
    ) {
        $this->logger = $logger;
        $this->orderFactory = $orderFactory;
        $this->pageFactory = $pageFactory;
        $this->storeManager = $storeManager;
        $this->dataObjectFactory = $dataObjectFactory;
        $this->resultJsonFactory = $resultJsonFactory;
        $this->config = $config;
        $this->orderService = $orderService;
        $this->invoiceService = $invoiceService;

        return parent::__construct($context);
    }

    /**
     * Execute.
     *
     * @return ResultInterface
     */
    public function execute()
    {
        /** @var JsonFactory $resultPage */
        $resultPage = $this->resultJsonFactory->create();

        if (!$this->getRequest()->getParams()) {
            $resultPage->setHttpResponseCode(404);

            return $resultPage;
        }

        $getnetData = $this->getRequest()->getParams();

        /** @var DataObjectFactory $getnetData */
        $getnetData = $this->dataObjectFactory->create(['data' => $getnetData]);

        $this->logger->debug(['type'=>'notification', 'data' => $getnetData->getData()]);

        $getnetDataSellerId = $getnetData->getSellerId();

        $sellerId = $this->config->getMerchantGatewaySellerId();

        if ($sellerId === $getnetDataSellerId) {
            $getnetDataOrderId = $getnetData->getOrderId();

            $order = $this->findMageOrder($getnetDataOrderId);

            if (!$order->getEntityId()) {
                return $this->createResult(
                    406,
                    [
                        'error'   => 406,
                        'message' => __('Order not found.'),
                    ]
                );
            }

            if ($order->getState() !== Order::STATE_NEW) {
                return $this->createResult(
                    412,
                    [
                        'error'   => 412,
                        'message' => __('Not available.'),
                    ]
                );
            }

            $getnetDataStatus = $getnetData->getStatus();

            return $this->resolveStatusUpdate($getnetDataStatus, $order);
        }

        return $this->createResult(401, []);
    }

    /**
     * Resolve Status Update.
     *
     * @param string                $getnetDataStatus
     * @param OrderInterfaceFactory $order
     *
     * @return ResultInterface
     */
    public function resolveStatusUpdate($getnetDataStatus, $order)
    {
        if ($getnetDataStatus === self::APPROVED_PAID ||
            $getnetDataStatus === self::ACCEPT_PAID ||
            $getnetDataStatus === self::ACCEPT_PAID_ALTERNATIVE) {
            return $this->processPay($order);
        }

        if ($getnetDataStatus === self::CANCELED_PAID ||
            $getnetDataStatus === self::DENNY_PAID ||
            $getnetDataStatus === self::ERROR) {
            return $this->processCancel($order);
        }

        return $this->createResult(412, []);
    }

    /**
     * Find Magento Order.
     *
     * @param string $getnetDataOrderId
     *
     * @return OrderInterfaceFactory|ResultInterface
     */
    public function findMageOrder($getnetDataOrderId)
    {
        try {
            /** @var OrderInterfaceFactory $order */
            $order = $this->orderFactory->create()->load($getnetDataOrderId, 'increment_id');
        } catch (Exception $exc) {
            return $this->createResult(
                500,
                [
                    'error'   => 500,
                    'message' => $exc->getMessage(),
                ]
            );
        }

        return $order;
    }

    /**
     * Process Pay.
     *
     * @param OrderInterfaceFactory $order
     *
     * @return ResultInterface
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
            return $this->createResult(
                500,
                [
                    'error'   => 500,
                    'message' => $exc->getMessage(),
                ]
            );
        }

        return $this->createResult(
            200,
            [
                'order'     => $order->getIncrementId(),
                'state'     => $order->getState(),
                'status'    => $order->getStatus(),
            ]
        );
    }

    /**
     * Process Cancel.
     *
     * @param OrderInterfaceFactory $order
     *
     * @return ResultInterface
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
            return $this->createResult(
                500,
                [
                    'error'   => 500,
                    'message' => $exc->getMessage(),
                ]
            );
        }

        return $this->createResult(
            200,
            [
                'order'     => $order->getIncrementId(),
                'state'     => $order->getState(),
                'status'    => $order->getStatus(),
            ]
        );
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

    /**
     * Create Result.
     *
     * @param int   $statusCode
     * @param array $data
     *
     * @return ResultInterface
     */
    public function createResult($statusCode, $data)
    {
        /** @var JsonFactory $resultPage */
        $resultPage = $this->resultJsonFactory->create();
        $resultPage->setHttpResponseCode($statusCode);
        $resultPage->setData($data);

        return $resultPage;
    }
}
