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
use Getnet\PaymentMagento\Gateway\Config\ConfigBoleto;
use Getnet\PaymentMagento\Gateway\Config\ConfigPix;
use Getnet\PaymentMagento\Gateway\Http\Client\PaymentInfoClient;
use Getnet\PaymentMagento\Model\PixBiometric\StatusUpdater;
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
     * Statuses that settle the order.
     */
    public const STATUSES_PAY = ['APPROVED', 'PAID', 'AUTHORIZED', 'CAPTURED'];

    /**
     * Statuses that cancel the order.
     */
    public const STATUSES_CANCEL = ['CANCELED', 'CANCELLED', 'DENIED', 'ERROR'];

    /**
     * Payment methods settled by notification (card has a synchronous answer; Wallet and
     * GetPay are confirmed by their own crons).
     */
    public const NOTIFIABLE_METHODS = [
        ConfigPix::METHOD,
        ConfigBoleto::METHOD,
    ];

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
     * @var StatusUpdater
     */
    protected $statusUpdater;

    /**
     * @var PaymentInfoClient
     */
    protected $paymentInfoClient;

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
     * @param StatusUpdater                  $statusUpdater
     * @param PaymentInfoClient              $paymentInfoClient
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
        Json $json,
        StatusUpdater $statusUpdater,
        PaymentInfoClient $paymentInfoClient
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
        $this->statusUpdater = $statusUpdater;
        $this->paymentInfoClient = $paymentInfoClient;
        parent::__construct($context);
    }

    /**
     * Create Csrf Validation Exception - notifications are bound to the order payment id instead.
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
        $notificationData = $this->getNotificationContent();

        if (!$notificationData) {
            return $this->createRejectedResult();
        }

        /** @var \Magento\Framework\DataObject $getnetData */
        $getnetData = $this->dataObjectFactory->create(['data' => $notificationData]);

        $this->logger->debug(['type'=>'notification', 'data' => $getnetData->getData()]);

        $sellerId = (string) $this->config->getMerchantGatewaySellerId();

        if ($sellerId === '' || $sellerId !== (string) $getnetData->getSellerId()) {
            return $this->createRejectedResult();
        }

        $order = $this->resolveOrderFromNotification($getnetData);

        if (!$this->isNotifiableOrder($order, (string) $getnetData->getId())) {
            // Unknown order, other payment method (card, Wallet, GetPay, third-party modules),
            // order already processed or payment id not bound to the order: same answer for all
            return $this->createRejectedResult();
        }

        if ($this->statusUpdater->isBiometricOrder($order)) {
            // Never trust the webhook status alone for Pix by biometrics: confirm it on the gateway
            $status = $this->statusUpdater->update($order);

            return $status === null ? $this->createUnavailableResult() : $this->createAcceptedResult();
        }

        if ($this->config->getApiType($order->getStoreId()) === Config::API_TYPE_GLOBAL) {
            return $this->resolveGlobalStatusUpdate($order);
        }

        return $this->resolveV2StatusUpdate($order, $getnetData);
    }

    /**
     * Check if the notification may change the order.
     *
     * Only Pix and Boleto orders still waiting for payment are handled here, and the
     * notified payment id must be the one Getnet returned when the order was placed.
     *
     * @param Order|null $order
     * @param string     $notifiedPaymentId
     *
     * @return bool
     */
    public function isNotifiableOrder($order, string $notifiedPaymentId): bool
    {
        if (!$order instanceof Order || !$order->getEntityId() || $notifiedPaymentId === '') {
            return false;
        }

        if ($order->getState() !== Order::STATE_NEW) {
            return false;
        }

        $payment = $order->getPayment();

        if (!$payment || !in_array($payment->getMethod(), self::NOTIFIABLE_METHODS, true)) {
            return false;
        }

        foreach ([(string) $payment->getLastTransId(), (string) $order->getExtOrderId()] as $boundId) {
            if ($boundId !== '' && hash_equals($boundId, $notifiedPaymentId)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Global API: apply the status confirmed on the gateway, never the notified one.
     *
     * @param Order $order
     *
     * @return ResultInterface
     */
    public function resolveGlobalStatusUpdate(Order $order)
    {
        $paymentId = (string) $order->getPayment()->getLastTransId();

        try {
            $info = $this->paymentInfoClient->fetch($paymentId, $order->getStoreId());
        } catch (Exception $exc) {
            $this->logger->debug([
                'type'      => 'notification_error',
                'exception' => $exc->getMessage(),
            ]);

            $info = null;
        }

        if ($info === null) {
            // Gateway could not confirm the payment: ask Getnet to retry the notification
            return $this->createUnavailableResult();
        }

        if (!$this->isExpectedAmount($order, $info['amount'])) {
            return $this->createRejectedResult();
        }

        return $this->resolveStatusUpdate($info['status'], $order);
    }

    /**
     * API V2: no payment query for Pix/Boleto, the notification is bound to the order.
     *
     * @param Order                         $order
     * @param \Magento\Framework\DataObject $getnetData
     *
     * @return ResultInterface
     */
    public function resolveV2StatusUpdate(Order $order, $getnetData)
    {
        $amount = $getnetData->getAmount();

        if (is_numeric($amount) && !$this->isExpectedAmount($order, (int) $amount)) {
            return $this->createRejectedResult();
        }

        return $this->resolveStatusUpdate(strtoupper((string) $getnetData->getStatus()), $order);
    }

    /**
     * Check if the gateway amount (in cents) matches the order total.
     *
     * @param Order    $order
     * @param int|null $amount
     *
     * @return bool
     */
    public function isExpectedAmount(Order $order, ?int $amount): bool
    {
        if ($amount === null) {
            return true;
        }

        return $amount === (int) round($this->config->formatPrice($order->getBaseGrandTotal()));
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

        $getnetDataId = $getnetData->getId();

        if (isset($getnetDataOrderId)) {
            $order = $this->findMageOrder($getnetDataOrderId);

            // Pix by biometrics sends a GUID as order_id: fall back to the payment id
            if (($order instanceof Order && $order->getEntityId()) || !isset($getnetDataId)) {
                return $order;
            }
        }

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
     * @param string $getnetDataStatus
     * @param Order  $order
     *
     * @return ResultInterface
     */
    public function resolveStatusUpdate($getnetDataStatus, $order)
    {
        if (in_array($getnetDataStatus, self::STATUSES_PAY, true)) {
            return $this->processPay($order);
        }

        if (in_array($getnetDataStatus, self::STATUSES_CANCEL, true)) {
            return $this->processCancel($order);
        }

        // Waiting/registered/pending: nothing to change, acknowledge the notification
        return $this->createAcceptedResult();
    }

    /**
     * Find Magento Order by the Getnet payment id.
     *
     * @param string $getnetDataId
     *
     * @return Order|null
     */
    public function findMageOrderById($getnetDataId)
    {
        $searchCriteria = $this->searchCriteria->addFilter('txn_id', (string) $getnetDataId)
            ->create();

        try {
            /** @var \Magento\Sales\Api\Data\TransactionInterface $transaction */
            $transaction = $this->transaction->getList($searchCriteria)->getFirstItem();

            if (!$transaction->getOrderId()) {
                return null;
            }

            $order = $this->orderFactory->create()->load($transaction->getOrderId());
        } catch (Exception $exc) {
            $this->logger->debug([
                'type'      => 'notification_error',
                'exception' => $exc->getMessage(),
            ]);

            return null;
        }

        return $order;
    }

    /**
     * Find Magento Order by increment id.
     *
     * @param string $getnetDataOrderId
     *
     * @return Order|null
     */
    public function findMageOrder($getnetDataOrderId)
    {
        try {
            /** @var Order $order */
            $order = $this->orderFactory->create()->load((string) $getnetDataOrderId, 'increment_id');
        } catch (Exception $exc) {
            $this->logger->debug([
                'type'      => 'notification_error',
                'exception' => $exc->getMessage(),
            ]);

            return null;
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
            $this->logger->debug([
                'type'      => 'notification_error',
                'exception' => $exc->getMessage(),
            ]);

            return $this->createUnavailableResult();
        }

        return $this->createAcceptedResult();
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
            $this->logger->debug([
                'type'      => 'notification_error',
                'exception' => $exc->getMessage(),
            ]);

            return $this->createUnavailableResult();
        }

        return $this->createAcceptedResult();
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
     * Notification processed (or nothing to change).
     *
     * @return ResultInterface
     */
    public function createAcceptedResult()
    {
        return $this->createResult(200, ['message' => __('Received.')]);
    }

    /**
     * Notification refused - same answer whatever the reason, so it can not be used to probe orders.
     *
     * @return ResultInterface
     */
    public function createRejectedResult()
    {
        return $this->createResult(412, ['message' => __('Not available.')]);
    }

    /**
     * Temporary failure - Getnet should retry the notification.
     *
     * @return ResultInterface
     */
    public function createUnavailableResult()
    {
        return $this->createResult(503, ['message' => __('Internal error.')]);
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
