<?php
/**
 * Copyright © Getnet. All rights reserved.
 *
 * @author    Bruno Elisei <brunoelisei@o2ti.com>
 * See LICENSE for license details.
 */

namespace Getnet\PaymentMagento\Gateway\Request\Card;

use Getnet\PaymentMagento\Gateway\Config\Config;
use Getnet\PaymentMagento\Gateway\Data\Order\OrderAdapterFactory;
use Getnet\PaymentMagento\Gateway\SubjectReader;
use InvalidArgumentException;
use Magento\Framework\Encryption\Encryptor;
use Magento\Payment\Gateway\Data\PaymentDataObjectInterface;
use Magento\Payment\Gateway\Request\BuilderInterface;

/**
 * Class Card Init Schema Data Request - Payment amount structure.
 */
class CardInitSchemaDataRequest implements BuilderInterface
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
     * Order Id Block Name.
     */
    public const ORDER_ID = 'order_id';

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
     * @param SubjectReader       $subjectReader
     * @param Encryptor           $encrytor
     * @param Config              $config
     * @param OrderAdapterFactory $orderAdapterFactory
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

        /** @var OrderAdapterFactory $orderAdapter * */
        $orderAdapter = $this->orderAdapterFactory->create(
            ['order' => $payment->getOrder()]
        );

        $result = [
            self::IDEMPOTENCY_KEY => $orderAdapter->getOrderIncrementId(),
            self::ORDER_ID        => $orderAdapter->getOrderIncrementId(),
            self::DATA            => [],
        ];

        return $result;
    }
}
