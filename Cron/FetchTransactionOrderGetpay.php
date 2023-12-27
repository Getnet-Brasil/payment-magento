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
use Magento\Sales\Model\ResourceModel\Order\CollectionFactory;

/*
 * Class Fetch Transaction Order Getpay - Cron fetch order
 */
class FetchTransactionOrderGetpay
{
    /**
     * @var Logger
     */
    protected $logger;

    /**
     * @var Order
     */
    protected $order;

    /**
     * @var ConfigGetpay
     */
    protected $configGetpay;

    /**
     * @var CollectionFactory
     */
    protected $collectionFactory;

    /**
     * @param Order             $order
     * @param Logger            $logger
     * @param ConfigGetpay      $configGetpay
     * @param CollectionFactory $collectionFactory
     */
    public function __construct(
        Order $order,
        Logger $logger,
        ConfigGetpay $configGetpay,
        CollectionFactory $collectionFactory
    ) {
        $this->order = $order;
        $this->logger = $logger;
        $this->configGetpay = $configGetpay;
        $this->collectionFactory = $collectionFactory;
    }

    /**
     * Execute.
     *
     * @return void
     */
    public function execute()
    {
        $orders = $this->collectionFactory->create()
        ->addFieldToFilter('state', [
            'in' => [
                Order::STATE_NEW,
            ],
        ]);

        $orders->getSelect()
                ->join(
                    ['sop' => 'sales_order_payment'],
                    'main_table.entity_id = sop.parent_id',
                    ['method']
                )
                ->where('sop.method = ?', ConfigGetpay::METHOD);

        foreach ($orders as $order) {
            if (!$order->getEntityId()) {
                continue;
            }
            $loadedOrder = $this->order->load($order->getEntityId());
            $payment = $loadedOrder->getPayment();

            try {
                $payment->update();
                $loadedOrder->save();
                $this->logger->debug([
                    'cron'   => 'FetchTransactionOrderGetpay',
                    'type'   => ConfigGetpay::METHOD,
                    'order'  => $loadedOrder->getIncrementId(),
                    'status' => $loadedOrder->getStatus(),
                ]);
            } catch (\Exception $exc) {
                continue;
            }
        }
    }
}
