<?php
/**
 * Copyright © Getnet. All rights reserved.
 *
 * @author    Bruno Elisei <brunoelisei@o2ti.com>
 * See LICENSE for license details.
 */

namespace Getnet\PaymentMagento\Gateway\Request\Card\Data\Additional\Customer;

use Getnet\PaymentMagento\Gateway\Config\Config;
use Getnet\PaymentMagento\Gateway\Data\Order\OrderAdapterFactory;
use Getnet\PaymentMagento\Gateway\Request\Card\CardInitSchemaDataRequest;
use Getnet\PaymentMagento\Gateway\Request\Card\Data\Additional\AdditionalInitSchemaDataRequest;
use Getnet\PaymentMagento\Gateway\SubjectReader;
use InvalidArgumentException;
use Magento\Payment\Gateway\Data\PaymentDataObjectInterface;
use Magento\Payment\Gateway\Request\BuilderInterface;
use Magento\Payment\Model\InfoInterface;

/**
 * Class Tax Document Data Request - Fiscal document data structure.
 */
class TaxDocumentDataRequest implements BuilderInterface
{
    /**
     * BillingAddress block name.
     */
    public const TAX_DOCUMENT = 'document_type';

    /**
     * The street number. 1 or 10 alphanumeric digits
     * Required.
     */
    public const TAX_DOCUMENT_NUMBER = 'document_number';

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
     * Get Value For Tax Document.
     *
     * @param OrderAdapterFactory $orderAdapter
     *
     * @return string
     */
    public function getValueForTaxDocument($orderAdapter)
    {
        $obtainTaxDocFrom = $this->config->getAddtionalValue('get_tax_document_from');

        $taxDocument = $orderAdapter->getCustomerTaxvat();

        if ($obtainTaxDocFrom === 'address') {
            $taxDocument = $orderAdapter->getBillingAddress()->getVatId();
        }

        return $taxDocument;
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
        $typeDocument = 'CPF';
        $result = [];

        /** @var OrderAdapterFactory $orderAdapter * */
        $orderAdapter = $this->orderAdapterFactory->create(
            ['order' => $payment->getOrder()]
        );

        $taxDocument = $this->getFiscalNumber($payment, $orderAdapter);
        $taxDocument = preg_replace('/[^0-9]/', '', (string) $taxDocument);

        if (strlen($taxDocument) === 14) {
            $typeDocument = 'CNPJ';
        }

        if ($typeDocument) {
            $result[CardInitSchemaDataRequest::DATA]
            [AdditionalInitSchemaDataRequest::ADDITIONAL_DATA]
            [CustomerDataRequest::CUSTOMER] = [
                    self::TAX_DOCUMENT        => $typeDocument,
                    self::TAX_DOCUMENT_NUMBER => $taxDocument,
                ];
        }

        return $result;
    }

    /**
     * Get Fiscal Number.
     *
     * @param InfoInterface       $payment
     * @param OrderAdapterFactory $orderAdapter
     *
     * @return string
     */
    public function getFiscalNumber($payment, $orderAdapter): ?string
    {
        $taxDocument = null;

        if ($payment->getAdditionalInformation('cc_holder_tax_document')) {
            $taxDocument = $payment->getAdditionalInformation('cc_holder_tax_document');
        }

        if ($payment->getAdditionalInformation('boleto_payer_tax_document')) {
            $taxDocument = $payment->getAdditionalInformation('boleto_payer_tax_document');
        }

        if (!$taxDocument) {
            $taxDocument = $this->getValueForTaxDocument($orderAdapter);
        }

        return $taxDocument;
    }
}
