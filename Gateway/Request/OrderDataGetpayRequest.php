<?php
/**
 * Copyright © Getnet. All rights reserved.
 *
 * @author    Bruno Elisei <brunoelisei@o2ti.com>
 * See LICENSE for license details.
 */

namespace Getnet\PaymentMagento\Gateway\Request;

use Getnet\PaymentMagento\Gateway\Config\Config;
use Getnet\PaymentMagento\Gateway\Config\ConfigGetpay;
use Getnet\PaymentMagento\Gateway\Data\Order\OrderAdapterFactory;
use Getnet\PaymentMagento\Gateway\SubjectReader;
use InvalidArgumentException;
use Magento\Payment\Gateway\Data\PaymentDataObjectInterface;
use Magento\Payment\Gateway\Request\BuilderInterface;

/**
 * Class Order Data Getpay Request - Payment amount structure.
 */
class OrderDataGetpayRequest implements BuilderInterface
{
    /**
     * Label Block Name.
     */
    public const LABEL = 'label';

    /**
     * Label Block Name.
     */
    public const EXPIRATION = 'expiration';

    /**
     * Max Orders Block Name.
     */
    public const MAX_ORDERS = 'max_orders';

    /**
     * Order Block Name.
     */
    public const ORDER = 'order';

    /**
     * Order Product Type Block Name.
     */
    public const ORDER_PRODUCT_TYPE = 'product_type';

    /**
     * Order Title Block Name.
     */
    public const ORDER_TITLE = 'title';

    /**
     * Order Description Block Name.
     */
    public const ORDER_DESCRIPTION = 'description';

    /**
     * Order Order Prefix Block Name.
     */
    public const ORDER_PREFIX = 'order_prefix';

    /**
     * Order Order Shipping Amount Block Name.
     */
    public const ORDER_SHIPPING_AMOUNT = 'shipping_amount';

    /**
     * Order Order Amount Block Name.
     */
    public const ORDER_AMOUNT = 'amount';

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
     * @var ConfigGetpay
     */
    protected $configGetpay;

    /**
     * @param SubjectReader       $subjectReader
     * @param OrderAdapterFactory $orderAdapterFactory
     * @param ConfigGetpay        $configGetpay
     * @param Config              $config
     */
    public function __construct(
        SubjectReader $subjectReader,
        OrderAdapterFactory $orderAdapterFactory,
        ConfigGetpay $configGetpay,
        Config $config
    ) {
        $this->subjectReader = $subjectReader;
        $this->orderAdapterFactory = $orderAdapterFactory;
        $this->configGetpay = $configGetpay;
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
            throw new InvalidArgumentException('Payment data object should be provided');
        }

        $paymentDO = $this->subjectReader->readPayment($buildSubject);
        $payment = $paymentDO->getPayment();
        $result = [];

        $order = $paymentDO->getOrder();

        $storeId = $order->getStoreId();

        /** @var OrderAdapterFactory $orderAdapter * */
        $orderAdapter = $this->orderAdapterFactory->create(
            ['order' => $payment->getOrder()]
        );

        $total = $order->getGrandTotalAmount();

        $result = [
            self::LABEL      => 'Magento',
            self::EXPIRATION => $this->configGetpay->getExpiration($storeId),
            self::MAX_ORDERS => 1,
            self::ORDER      => [
                self::ORDER_PRODUCT_TYPE    => $this->configGetpay->getProductType($storeId),
                self::ORDER_TITLE           => __('Payment for order #%1', $order->getOrderIncrementId()),
                self::ORDER_DESCRIPTION     => __('Payment link'),
                self::ORDER_PREFIX          => $order->getOrderIncrementId(),
                self::ORDER_AMOUNT          => $this->config->formatPrice(
                    $total
                ),
            ],
        ];

        return $result;
    }
}
