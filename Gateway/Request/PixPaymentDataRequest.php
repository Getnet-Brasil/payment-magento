<?php
/**
 * Copyright © Getnet. All rights reserved.
 *
 * @author    Bruno Elisei <brunoelisei@o2ti.com>
 * See LICENSE for license details.
 */

namespace Getnet\PaymentMagento\Gateway\Request;

use Getnet\PaymentMagento\Gateway\Config\Config;
use Getnet\PaymentMagento\Gateway\Config\ConfigBoleto;
use Getnet\PaymentMagento\Gateway\Data\Order\OrderAdapterFactory;
use Getnet\PaymentMagento\Gateway\SubjectReader;
use InvalidArgumentException;
use Magento\Payment\Gateway\Data\PaymentDataObjectInterface;
use Magento\Payment\Gateway\Request\BuilderInterface;

/**
 * Class Pix Payment Data Request - Payment data structure for boleto.
 */
class PixPaymentDataRequest implements BuilderInterface
{
    /**
     * Method - Block Name.
     */
    public const METHOD = 'boleto';

    /**
     * Installment count - Number of payment installments.
     */
    public const DOCUMENT_NUMBER = 'document_number';

    /**
     * Boleto expiration date - Due date.
     */
    public const BOLETO_EXPIRATION_DATE = 'expiration_date';

    /**
     * Boleto Instruction - Block name.
     */
    public const BOLETO_INSTRUCTION = 'instructions';

    /**
     * @var SubjectReader
     */
    protected $subjectReader;

    /**
     * @var Config
     */
    protected $config;

    /**
     * @var Config Boleto
     */
    protected $configBoleto;

    /**
     * @param SubjectReader $subjectReader
     * @param Config        $config
     * @param ConfigBoleto  $configBoleto
     */
    public function __construct(
        SubjectReader $subjectReader,
        Config $config,
        ConfigBoleto $configBoleto
    ) {
        $this->subjectReader = $subjectReader;
        $this->config = $config;
        $this->configBoleto = $configBoleto;
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

        $paymentDO = $buildSubject['payment'];
        $order = $paymentDO->getOrder();
        $storeId = $order->getStoreId();
        $result = $this->getDataPaymetBoleto($order, $storeId);

        return $result;
    }

    /**
     * Data for Boleto.
     *
     * @param OrderAdapterFactory $order
     * @param int                 $storeId
     *
     * @return array
     */
    public function getDataPaymetBoleto($order, $storeId)
    {
        $instruction = [];
        $instruction[self::METHOD] = [
            self::DOCUMENT_NUMBER          => $order->getOrderIncrementId(),
            self::BOLETO_INSTRUCTION       => $this->configBoleto->getInstructionLine($storeId),
            self::BOLETO_EXPIRATION_DATE   => $this->configBoleto->getExpiration($storeId),
        ];

        return $instruction;
    }
}
