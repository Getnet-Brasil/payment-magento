<?php
/**
 * Copyright © Getnet. All rights reserved.
 *
 * @author    Bruno Elisei <brunoelisei@o2ti.com>
 * See LICENSE for license details.
 */

declare(strict_types=1);

namespace Getnet\PaymentMagento\Cron;

use Exception;
use Getnet\PaymentMagento\Gateway\Config\ConfigPix;
use Getnet\PaymentMagento\Model\PixBiometric\StatusUpdater;
use Magento\Framework\App\Area;
use Magento\Payment\Model\Method\Logger;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\ResourceModel\Order\CollectionFactory;
use Magento\Store\Model\App\Emulation;

/**
 * Consolidate pending Pix by biometrics orders.
 *
 * An expired biometric charge turns DENIED on the gateway without any webhook, so pending
 * orders are polled until they are approved or canceled.
 */
class FetchTransactionOrderPixBiometric
{
    /**
     * Only orders created in this window are polled (hours).
     */
    public const LOOKBACK_HOURS = 48;

    /**
     * Maximum orders consolidated per run.
     */
    public const BATCH_SIZE = 200;

    /**
     * @var CollectionFactory
     */
    protected $collectionFactory;

    /**
     * @var OrderRepositoryInterface
     */
    protected $orderRepository;

    /**
     * @var StatusUpdater
     */
    protected $statusUpdater;

    /**
     * @var Logger
     */
    protected $logger;

    /**
     * @var Emulation
     */
    protected $emulation;

    /**
     * @param CollectionFactory        $collectionFactory
     * @param OrderRepositoryInterface $orderRepository
     * @param StatusUpdater            $statusUpdater
     * @param Logger                   $logger
     * @param Emulation                $emulation
     */
    public function __construct(
        CollectionFactory $collectionFactory,
        OrderRepositoryInterface $orderRepository,
        StatusUpdater $statusUpdater,
        Logger $logger,
        Emulation $emulation
    ) {
        $this->collectionFactory = $collectionFactory;
        $this->orderRepository = $orderRepository;
        $this->statusUpdater = $statusUpdater;
        $this->logger = $logger;
        $this->emulation = $emulation;
    }

    /**
     * Execute.
     *
     * @return void
     */
    public function execute()
    {
        $collection = $this->collectionFactory->create()
            ->addFieldToFilter('main_table.state', Order::STATE_NEW)
            ->addFieldToFilter(
                'main_table.created_at',
                ['gteq' => gmdate('Y-m-d H:i:s', time() - (self::LOOKBACK_HOURS * 3600))]
            );

        $collection->getSelect()
            ->join(
                ['sop' => $collection->getTable('sales_order_payment')],
                'main_table.entity_id = sop.parent_id',
                []
            )
            ->where('sop.method = ?', ConfigPix::METHOD)
            ->where('sop.additional_information LIKE ?', '%"'.ConfigPix::MODE.'":"'.ConfigPix::MODE_BIOMETRIC.'"%');

        $collection->setOrder('main_table.entity_id', 'ASC')->setPageSize(self::BATCH_SIZE)->setCurPage(1);

        foreach ($collection->getColumnValues('entity_id') as $orderId) {
            try {
                /** @var Order $order */
                $order = $this->orderRepository->get((int) $orderId);

                // Store emulation loads the store locale, so order comments are translated
                $this->emulation->startEnvironmentEmulation((int) $order->getStoreId(), Area::AREA_FRONTEND, true);

                try {
                    $status = $this->statusUpdater->update($order);
                } finally {
                    $this->emulation->stopEnvironmentEmulation();
                }

                $this->logger->debug([
                    'cron'           => 'FetchTransactionOrderPixBiometric',
                    'order'          => $order->getIncrementId(),
                    'gateway_status' => $status,
                    'state'          => $order->getState(),
                ]);
            } catch (Exception $exc) {
                $this->logger->debug([
                    'cron'  => 'FetchTransactionOrderPixBiometric',
                    'order' => $orderId,
                    'error' => $exc->getMessage(),
                ]);
            }
        }
    }
}
