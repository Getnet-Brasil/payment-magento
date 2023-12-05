<?php
/**
 * Copyright © Getnet. All rights reserved.
 *
 * @author    Bruno Elisei <brunoelisei@o2ti.com>
 * See LICENSE for license details.
 */

namespace Getnet\PaymentMagento\Gateway\Request\Card\Data\Additional\Order\Items;

use Getnet\PaymentMagento\Gateway\Config\Config;
use Getnet\PaymentMagento\Gateway\Data\Order\OrderAdapterFactory;
use Getnet\PaymentMagento\Gateway\Request\Card\CardInitSchemaDataRequest;
use Getnet\PaymentMagento\Gateway\Request\Card\Data\Additional\AdditionalInitSchemaDataRequest;
use Getnet\PaymentMagento\Gateway\SubjectReader;
use InvalidArgumentException;
use Magento\Payment\Gateway\Data\PaymentDataObjectInterface;
use Magento\Payment\Gateway\Request\BuilderInterface;

/**
 * Class Items Data Request - Item structure for orders.
 */
class ItemsDataRequest implements BuilderInterface
{
    /**
     * Order block name.
     */
    public const ORDER = 'order';

    /**
     * Items block name.
     */
    public const ITEMS = 'items';

    /**
     * Item name block name.
     */
    public const ITEM_NAME = 'name';

    /**
     * Item unit amount block name.
     */
    public const ITEM_PRICE = 'price';

    /**
     * Item quantity block name.
     */
    public const ITEM_QUANTITY = 'quantity';

    /**
     * Itens Sku block Name.
     */
    public const ITEM_SKU = 'sku';

    /**
     * @var SubjectReader
     */
    protected $subjectReader;

    /**
     * @var OrderAdapterFactory
     */
    protected $orderAdapterFactory;

    /**
     * @var Config
     */
    protected $config;

    /**
     * @param SubjectReader       $subjectReader
     * @param OrderAdapterFactory $orderAdapterFactory
     * @param Config              $config
     */
    public function __construct(
        SubjectReader $subjectReader,
        OrderAdapterFactory $orderAdapterFactory,
        Config $config
    ) {
        $this->subjectReader = $subjectReader;
        $this->orderAdapterFactory = $orderAdapterFactory;
        $this->config = $config;
    }

    /**
     * Build.
     *
     * @param array $buildSubject
     */
    public function build(array $buildSubject)
    {
        if (!isset($buildSubject['payment'])
        || !$buildSubject['payment'] instanceof PaymentDataObjectInterface
        ) {
            throw new InvalidArgumentException(__('Payment data object should be provided'));
        }

        $result = [];

        $paymentDO = $this->subjectReader->readPayment($buildSubject);
        $payment = $paymentDO->getPayment();

        $result = [];

        /** @var OrderAdapterFactory $orderAdapter * */
        $orderAdapter = $this->orderAdapterFactory->create(
            ['order' => $payment->getOrder()]
        );

        $result = $this->getPurchaseItems($orderAdapter);

        return $result;
    }

    /**
     * Get Purchase Items.
     *
     * @param \Magento\Sales\Model\Order $order
     *
     * @return array
     */
    public function getPurchaseItems(
        $order
    ) {
        $result = [];
        $items = $order->getItems();

        foreach ($items as $item) {
            if ($item->getParentItem()) {
                continue;
            }

            $result[CardInitSchemaDataRequest::DATA]
            [AdditionalInitSchemaDataRequest::ADDITIONAL_DATA]
            [self::ORDER][self::ITEMS][] = [
                    self::ITEM_NAME     => $item->getName(),
                    self::ITEM_PRICE    => $this->config->formatPrice($item->getPrice()),
                    self::ITEM_QUANTITY => (int) $item->getQtyOrdered(),
                    self::ITEM_SKU      => $item->getSku(),
                ];
        }

        return $result;
    }
}
