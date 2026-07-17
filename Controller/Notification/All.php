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
use InvalidArgumentException;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\CsrfAwareActionInterface;
use Magento\Framework\App\Request\InvalidRequestException;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\DataObjectFactory;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Framework\View\Result\PageFactory;
use Magento\Payment\Model\Method\Logger;
use Magento\Sales\Api\Data\OrderInterfaceFactory;
use Magento\Sales\Api\Data\TransactionSearchResultInterfaceFactory as TransactionSearch;
use Magento\Sales\Api\TransactionRepositoryInterface;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Service\InvoiceService;
use Magento\Sales\Model\Service\OrderService;
use Magento\Store\Model\StoreManagerInterface;

/**
 * Controler Notification All - Notification of receivers for All Methods.
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class All extends Action implements CsrfAwareActionInterface
{
    /**
     * @const string
     */
    public const APPROVED_PAID = 'APPROVED';

    /**
     * @const string
     */
    public const PENDING = 'PENDING';

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
     * @var Json
     */
    protected $json;

    /**
     * @param Context                        $context
     * @param Logger                         $logger
     * @param OrderInterfaceFactory          $orderFactory
     * @param SearchCriteriaBuilder          $searchCriteria
     * @param PageFactory                    $pageFactory
     * @param StoreManagerInterface          $storeManager
     * @param TransactionSearch              $transactionSearch
     * @param TransactionRepositoryInterface $transaction
     * @param DataObjectFactory              $dataObjectFactory
     * @param JsonFactory                    $resultJsonFactory
     * @param Config                         $config
     * @param OrderService                   $orderService
     * @param InvoiceService                 $invoiceService
     * @param Json                           $json
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
        InvoiceService $invoiceService,
        Json $json
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
        $this->json = $json;
        parent::__construct($context);
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

    /**
     * Execute.
     *
     * @return ResultInterface
     *
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    public function execute()
    {
        /** @var JsonFactory $resultPage */
        $resultPage = $this->resultJsonFactory->create();
        $order = null;

        $notificationData = $this->getNotificationContent();

        if (!$notificationData) {
            $resultPage->setHttpResponseCode(404);

            return $resultPage;
        }

        /** @var DataObjectFactory $getnetData */
        $getnetData = $this->dataObjectFactory->create(['data' => $notificationData]);

        $this->logger->debug(['type'=>'notification', 'data' => $getnetData->getData()]);

        $getnetDataSellerId = $getnetData->getSellerId();

        $sellerId = $this->config->getMerchantGatewaySellerId();

        if ($sellerId === $getnetDataSellerId) {
            $order = $this->resolveOrderFromNotification($getnetData);

            if ($order === null || !$order->getEntityId()) {
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
     * Resolve the Magento order referenced by a notification.
     *
     * The order id (V2) takes precedence; the Getnet payment id (Global) is the fallback.
     *
     * @param \Magento\Framework\DataObject $getnetData
     *
     * @return Order|null
     */
    private function resolveOrderFromNotification($getnetData)
    {
        $getnetDataOrderId = $getnetData->getOrderId();

        if (isset($getnetDataOrderId)) {
            return $this->findMageOrder($getnetDataOrderId);
        }

        $getnetDataId = $getnetData->getId();

        if (isset($getnetDataId)) {
            return $this->findMageOrderById($getnetDataId);
        }

        return null;
    }

    /**
     * Get Notification Content - request params (API V2) or JSON body (API Global).
     *
     * @return array
     */
    public function getNotificationContent(): array
    {
        $content = $this->getRequest()->getParams();
        $content = is_array($content) ? $content : [];

        $bodyContent = [];

        try {
            $bodyContent = $this->json->unserialize((string) $this->getRequest()->getContent());
        } catch (InvalidArgumentException $exc) {
            $bodyContent = [];
        }

        if (is_array($bodyContent) && $bodyContent) {
            // JSON body values win over query/route params (Global API notifications)
            $content = array_merge($content, $bodyContent);
        }

        if (!$content) {
            return [];
        }

        return $this->normalizeNotification($content);
    }

    /**
     * Normalize Global API notification fields to the V2 names used by this controller.
     *
     * Global webhooks send payment_id (not id) and have no payment_type — the boleto
     * flow is derived from the boleto object or payment_method.
     *
     * @param array $content
     *
     * @return array
     */
    public function normalizeNotification(array $content): array
    {
        if (empty($content['id']) && !empty($content['payment_id'])) {
            $content['id'] = $content['payment_id'];
        }

        if (empty($content['payment_type'])) {
            $paymentMethod = strtoupper((string) ($content['payment_method'] ?? ''));

            if (isset($content['boleto']) || $paymentMethod === 'BOLETO') {
                $content['payment_type'] = 'boleto';
            }
        }

        return $content;
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
     * Update Payment Id.
     *
     * @param OrderInterfaceFactory $order
     * @param string                $getnetDataId
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
