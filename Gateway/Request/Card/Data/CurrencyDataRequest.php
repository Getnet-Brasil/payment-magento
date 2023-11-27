<?php
/**
 * Copyright © Getnet. All rights reserved.
 *
 * @author    Bruno Elisei <brunoelisei@o2ti.com>
 * See LICENSE for license details.
 */

namespace Getnet\PaymentMagento\Gateway\Request\Card\Data;

use Getnet\PaymentMagento\Gateway\Config\Config;
use Getnet\PaymentMagento\Gateway\Config\ConfigCc;
use Getnet\PaymentMagento\Gateway\SubjectReader;
use InvalidArgumentException;
use Magento\Payment\Gateway\Data\PaymentDataObjectInterface;
use Magento\Payment\Gateway\Request\BuilderInterface;
use Getnet\PaymentMagento\Gateway\Request\Card\CardInitSchemaDataRequest;

/**
 * Class Currency Data Request - Payment Currency structure.
 */
class CurrencyDataRequest implements BuilderInterface
{
    /**
     * Currency block name.
     */
    public const CURRENCY = 'currency';

    /**
     * @var SubjectReader
     */
    protected $subjectReader;

    /**
     * @var Config
     */
    protected $config;

    /**
     * @var ConfigCc
     */
    protected $configCc;

    /**
     * @param SubjectReader $subjectReader
     * @param Config        $config
     * @param ConfigCc      $configCc
     */
    public function __construct(
        SubjectReader $subjectReader,
        Config $config,
        ConfigCc $configCc
    ) {
        $this->subjectReader = $subjectReader;
        $this->config = $config;
        $this->configCc = $configCc;
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

        $result[CardInitSchemaDataRequest::DATA][self::CURRENCY] = $order->getCurrencyCode();

        return $result;
    }
}
