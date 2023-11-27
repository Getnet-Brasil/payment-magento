<?php
/**
 * Copyright © Getnet. All rights reserved.
 *
 * @author    Bruno Elisei <brunoelisei@o2ti.com>
 * See LICENSE for license details.
 */

namespace Getnet\PaymentMagento\Gateway\Request\Boleto\Data;

use Getnet\PaymentMagento\Gateway\Config\Config;
use Getnet\PaymentMagento\Gateway\SubjectReader;
use InvalidArgumentException;
use Magento\Payment\Gateway\Data\PaymentDataObjectInterface;
use Magento\Payment\Gateway\Request\BuilderInterface;
use Getnet\PaymentMagento\Gateway\Request\Boleto\BoletoInitSchemaDataRequest;

/**
 * Class Amount Data Request - Payment amount structure.
 */
class AmountDataRequest implements BuilderInterface
{
    /**
     * Amount block name.
     */
    public const AMOUNT = 'amount';

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

        $grandTotal = $order->getGrandTotalAmount();

        $result[BoletoInitSchemaDataRequest::DATA][self::AMOUNT] = $this->config->formatPrice($grandTotal);

        return $result;
    }
}
