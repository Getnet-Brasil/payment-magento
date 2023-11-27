<?php
/**
 * Copyright © Getnet. All rights reserved.
 *
 * @author    Bruno Elisei <brunoelisei@o2ti.com>
 * See LICENSE for license details.
 */

namespace Getnet\PaymentMagento\Gateway\Request\Boleto;

use Getnet\PaymentMagento\Gateway\SubjectReader;
use Getnet\PaymentMagento\Gateway\Data\Order\OrderAdapterFactory;
use InvalidArgumentException;
use Magento\Payment\Gateway\Data\PaymentDataObjectInterface;
use Magento\Payment\Gateway\Request\BuilderInterface;
use Magento\Framework\Encryption\Encryptor;
use Getnet\PaymentMagento\Gateway\Config\Config;

/**
 * Class Boleto Init Schema Data Request - Payment amount structure.
 */
class BoletoInitSchemaDataRequest implements BuilderInterface
{
    /**
     * Idempotency Key Block Name.
     */
    public const IDEMPOTENCY_KEY = 'idempotency_key';

    /**
     * Request Id Block Name.
     */
    public const REQUEST_ID = 'request_id';

    /**
     * Data Block Name.
     */
    public const DATA = 'data';

    /**
     * @var SubjectReader
     */
    protected $subjectReader;

    /**
     * @var Encryptor
     */
    protected $encrytor;

    /**
     * @var Config
     */
    protected $config;

    /**
     * @var OrderAdapterFactory
     */
    protected $orderAdapterFactory;

    /**
     * @param SubjectReader         $subjectReader
     * @param Encryptor             $encrytor
     * @param Config                $config
     * @param OrderAdapterFactory   $orderAdapterFactory
     */
    public function __construct(
        SubjectReader $subjectReader,
        Encryptor $encrytor,
        Config $config,
        OrderAdapterFactory $orderAdapterFactory
    ) {
        $this->subjectReader = $subjectReader;
        $this->encrytor = $encrytor;
        $this->config = $config;
        $this->orderAdapterFactory = $orderAdapterFactory;
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

        $result = [];
        
        $payment = $paymentDO->getPayment();

        $order = $paymentDO->getOrder();

        /** @var OrderAdapterFactory $orderAdapter * */
        $orderAdapter = $this->orderAdapterFactory->create(
            ['order' => $payment->getOrder()]
        );

        $storeId = $order->getStoreId();

        $result = [
            self::IDEMPOTENCY_KEY => $orderAdapter->getOrderIncrementId(),
            self::REQUEST_ID      => $this->config->getMerchantGatewaySellerId($storeId),
            self::DATA            => []
        ];

        return $result;
    }
}
