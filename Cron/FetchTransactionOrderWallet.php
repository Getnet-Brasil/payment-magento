<?php
/**
 * Copyright © Getnet. All rights reserved.
 *
 * @author    Bruno Elisei <brunoelisei@o2ti.com>
 * See LICENSE for license details.
 */

namespace Getnet\PaymentMagento\Cron;

use Getnet\PaymentMagento\Gateway\Config\ConfigWallet;
use Magento\Payment\Model\Method\Logger;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\ResourceModel\Order\CollectionFactory;

/*
 * Class Fetch Transaction Order Wallet - Cron fetch order
 */
class FetchTransactionOrderWallet
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
     * @var ConfigWallet
     */
    protected $configWallet;

    /**
     * @var CollectionFactory
     */
    protected $collectionFactory;

    /**
     * @param Order             $order
     * @param Logger            $logger
     * @param ConfigWallet      $configWallet
     * @param CollectionFactory $collectionFactory
     */
    public function __construct(
        Order $order,
        Logger $logger,
        ConfigWallet $configWallet,
        CollectionFactory $collectionFactory
    ) {
        $this->order = $order;
        $this->logger = $logger;
        $this->configWallet = $configWallet;
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
                ->where('sop.method = ?', ConfigWallet::METHOD);

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
                    'cron'   => 'FetchTransactionOrderWallet',
                    'type'   => ConfigWallet::METHOD,
                    'order'  => $loadedOrder->getIncrementId(),
                    'status' => $loadedOrder->getStatus(),
                ]);
            } catch (\Exception $exc) {
                continue;
            }
        }
    }
}
