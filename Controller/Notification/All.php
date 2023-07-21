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
use Magento\Framework\Api\SearchCriteriaBuilder;
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
use Magento\Sales\Api\TransactionRepositoryInterface;
use Magento\Sales\Api\Data\TransactionSearchResultInterfaceFactory as TransactionSearch;

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
    public const PENDING = "PENDING";

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
     * @var SearchCriteriaBuilder
     */
    protected $searchCriteria;

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
     * @var TransactionSearch
     */
    protected $transactionSearch;

    /**
     * @var TransactionRepositoryInterface
     */
    protected $transaction;

    /**
     * @param Context               $context
     * @param Logger                $logger
     * @param OrderInterfaceFactory $orderFactory
     * @param SearchCriteriaBuilder $searchCriteria
     * @param PageFactory           $pageFactory
     * @param StoreManagerInterface $storeManager
     * @param TransactionSearch     $transactionSearch
     * @param TransactionRepositoryInterface $transaction
     * @param DataObjectFactory     $dataObjectFactory
     * @param JsonFactory           $resultJsonFactory
     * @param Config                $config
     * @param OrderService          $orderService
     * @param InvoiceService        $invoiceService
     *
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     */
    public function __construct(
        Context $context,
        Logger $logger,
        OrderInterfaceFactory $orderFactory,
        SearchCriteriaBuilder $searchCriteria,
        PageFactory $pageFactory,
        StoreManagerInterface $storeManager,
        TransactionSearch $transactionSearch,
        TransactionRepositoryInterface $transaction,
        DataObjectFactory $dataObjectFactory,
        JsonFactory $resultJsonFactory,
        Config $config,
        OrderService $orderService,
        InvoiceService $invoiceService
    ) {
        $this->logger = $logger;
        $this->orderFactory = $orderFactory;
        $this->searchCriteria = $searchCriteria;
        $this->pageFactory = $pageFactory;
        $this->storeManager = $storeManager;
        $this->transactionSearch = $transactionSearch;
        $this->transaction = $transaction;
        $this->dataObjectFactory = $dataObjectFactory;
        $this->resultJsonFactory = $resultJsonFactory;
        $this->config = $config;
        $this->orderService = $orderService;
        $this->invoiceService = $invoiceService;
        parent::__construct($context);
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

            $getnetDataId = $getnetData->getId();

            if (isset($getnetDataOrderId)) {
                $order = $this->findMageOrder($getnetDataOrderId);
            }

            if (isset($getnetDataId) && !isset($getnetDataOrderId)) {
                $order = $this->findMageOrderById($getnetDataId);
            }

            if (!$order->getEntityId()) {
                return $this->createResult(
                    406,
                    [
                        'error'   => 406,
                        'message' => __('Order not found.'),
                    ]
                );
            }
           
            if ($order->getState() === Order::STATE_NEW) {
                $paymentType = $getnetData->getPaymentType();

                if ($paymentType === 'boleto') {
                    $getnetDataStatus = $getnetData->getStatus();
                    $getnetDataId = $getnetData->getId();
                    
                    return $this->resolveStatusUpdate($getnetDataStatus, $order, $getnetDataId);
                }
               
            }

            if ($order->getState() !== Order::STATE_NEW) {
                return $this->createResult(
                    412,
                    [
                        'error'   => 412,
                        'status'  => $order->getState(),
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
     * @param string|null           $getnetDataId
     *
     * @return ResultInterface
     */
    public function resolveStatusUpdate($getnetDataStatus, $order, $getnetDataId = null)
    {
        if ($getnetDataStatus === self::PENDING) {
            return $this->updatePayId($order, $getnetDataId);
        }

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
     * Update Payment Id
     *
     * @param OrderInterfaceFactory $order
     * @param string $getnetDataPaymentId
     */
    public function updatePayId($order, $getnetDataId)
    {
        
        try {
            $orderId = $order->getId();
            $transaction = $this->transactionSearch->create()->addOrderIdFilter($orderId)->getFirstItem();
            $transaction->setTxnId($getnetDataId);
            $transaction->save();

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
                'pay'       => $getnetDataId,
            ]
        );
    }
    
    /**
     * Find Magento Order.
     *
     * @param string $getnetDataId
     *
     * @return OrderInterfaceFactory|ResultInterface
     */
    public function findMageOrderById($getnetDataId)
    {
        $searchCriteria = $this->searchCriteria->addFilter('txn_id', $getnetDataId)
            ->create();

        try {
            /** @var TransactionRepositoryInterface $transaction */
            $transaction = $this->transaction->getList($searchCriteria)->getFirstItem();

            $order = $this->orderFactory->create()->load($transaction->getOrderId());

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
