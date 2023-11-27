<?php
/**
 * Copyright © Getnet. All rights reserved.
 *
 * @author    Bruno Elisei <brunoelisei@o2ti.com>
 * See LICENSE for license details.
 */

namespace Getnet\PaymentMagento\Gateway\Request\Card\Operations;

use Getnet\PaymentMagento\Gateway\SubjectReader;
use InvalidArgumentException;
use Magento\Payment\Gateway\Data\PaymentDataObjectInterface;
use Magento\Payment\Gateway\Request\BuilderInterface;
use Getnet\PaymentMagento\Gateway\Config\Config;

/**
 * Class Idempotency Key Data Request - Payment amount structure.
 */
class IdempotencyKeyDataRequest implements BuilderInterface
{
    /**
     * Order Id Block Name.
     */
    public const IDEMPOTENCY_KEY = 'idempotency_key';

    /**
     * @var SubjectReader
     */
    protected $subjectReader;

    /**
     * @var Config
     */
    protected $config;

    /**
     * @param SubjectReader $subjectReader
     * @param Config        $config
     */
    public function __construct(
        SubjectReader $subjectReader,
        Config $config
    ) {
        $this->subjectReader = $subjectReader;
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

        $result = [];

        $order = $paymentDO->getOrder();

        $result[self::IDEMPOTENCY_KEY] = $order->getOrderIncrementId().'-capture';

        return $result;
    }
}
