<?php
/**
 * Copyright © Getnet. All rights reserved.
 *
 * @author    Bruno Elisei <brunoelisei@o2ti.com>
 * See LICENSE for license details.
 */

namespace Getnet\PaymentMagento\Cron;

use Getnet\PaymentMagento\Gateway\Config\ConfigGetpay;
use Magento\Payment\Model\Method\Logger;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\ResourceModel\Order\Creditmemo\CollectionFactory;
use Getnet\PaymentMagento\Model\ConsultRefundManagement;
use Magento\Sales\Model\Order\Creditmemo;

/*
 * Class Fetch Transaction Refund - Cron fetch refund
 */
class FetchTransactionRefund
{
    /**
     * @var String
     */
    public const STATUS_PROCESSING_CANCEL_CODE_PENDING = 100;

    /**
     * @var String
     */
    public const STATUS_PROCESSING_CANCEL_CODE_SUCCESS = 0;

    /**
     * @var Logger
     */
    protected $logger;

    /**
     * @var Order
     */
    protected $order;

    /**
     * @var CollectionFactory
     */
    protected $collectionFactory;

    /**
     * @var ConsultRefundManagement
     */
    protected $consultRefund;

    /**
     * @param Order                     $order
     * @param Logger                    $logger
     * @param CollectionFactory         $collectionFactory
     * @param ConsultRefundManagement   $consultRefund
     */
    public function __construct(
        Order $order,
        Logger $logger,
        CollectionFactory $collectionFactory,
        ConsultRefundManagement $consultRefund
    ) {
        $this->order = $order;
        $this->logger = $logger;
        $this->collectionFactory = $collectionFactory;
        $this->consultRefund = $consultRefund;
    }

    /**
     * Execute.
     *
     * @return void
     */
    public function execute()
    {
        /** @var \Magento\Sales\Model\ResourceModel\Order\Creditmemo\CollectionFactory $creditMemos */
        $creditMemos = $this->collectionFactory->create()
                    ->addFieldToFilter('state', 1);

        foreach ($creditMemos as $creditMemo) {

            if (!$creditMemo->getTransactionId()) {
                continue;
            }

            $transactionId = $creditMemo->getTransactionId();

            $storeId = $creditMemo->getStoreId();

            $data = $this->consultRefund->getRefundData($storeId, $transactionId);

            $this->logger->debug([
                'cron'          => 'FetchTransactionRefund-Before',
                'transactionId' => $transactionId,
                'status'        => $creditMemo->getState(),
                'data'          => $data
            ]);

            $getnetState = (int) $data['status_processing_cancel_code'];
            
            if ($getnetState === self::STATUS_PROCESSING_CANCEL_CODE_PENDING) {
                $creditMemo->setState(Creditmemo::STATE_OPEN);
            }

            if ($getnetState === self::STATUS_PROCESSING_CANCEL_CODE_SUCCESS) {
                $creditMemo->setState(Creditmemo::STATE_REFUNDED);
                $comment = $data['status_processing_cancel_message'];
                $creditMemo->addComment($comment, true, true);
            }

            if ($getnetState !== self::STATUS_PROCESSING_CANCEL_CODE_SUCCESS
                && $getnetState !== self::STATUS_PROCESSING_CANCEL_CODE_PENDING) {
                $creditMemo->setState(Creditmemo::STATE_CANCELED);
                $comment = $data['status_processing_cancel_message'];
                $creditMemo->addComment($comment, true, true);
            }

            try {
                $creditMemo->save();

                $this->logger->debug([
                    'cron'          => 'FetchTransactionRefund-After',
                    'transactionId' => $transactionId,
                    'status'        => $creditMemo->getState(),
                    'data'          => $data
                ]);
    
            } catch (\Exception $exc) {
                continue;
            }
        }
    }
}
