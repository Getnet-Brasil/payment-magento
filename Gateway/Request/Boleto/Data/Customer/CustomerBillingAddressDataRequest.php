<?php
/**
 * Copyright © Getnet. All rights reserved.
 *
 * @author    Bruno Elisei <brunoelisei@o2ti.com>
 * See LICENSE for license details.
 */

namespace Getnet\PaymentMagento\Gateway\Request\Boleto\Data\Customer;

use Getnet\PaymentMagento\Gateway\Config\Config;
use Getnet\PaymentMagento\Gateway\Data\Order\OrderAdapterFactory;
use Getnet\PaymentMagento\Gateway\Request\Boleto\BoletoInitSchemaDataRequest;
use Getnet\PaymentMagento\Gateway\SubjectReader;
use InvalidArgumentException;
use Magento\Payment\Gateway\Data\PaymentDataObjectInterface;
use Magento\Payment\Gateway\Request\BuilderInterface;

/**
 * Class Customer Billing Address Data Builder - Customer Address structure.
 */
class CustomerBillingAddressDataRequest implements BuilderInterface
{
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
            throw new InvalidArgumentException('Payment data object should be provided');
        }

        $paymentDO = $this->subjectReader->readPayment($buildSubject);
        $payment = $paymentDO->getPayment();

        $result = [];

        /** @var OrderAdapterFactory $orderAdapter * */
        $orderAdapter = $this->orderAdapterFactory->create(
            ['order' => $payment->getOrder()]
        );

        $billingAddress = $orderAdapter->getBillingAddress();
        if ($billingAddress) {
            // phpcs:ignore Generic.Files.LineLength
            $result[BoletoInitSchemaDataRequest::DATA][CustomerDataRequest::CUSTOMER][AddressDataRequest::BILLING_ADDRESS]
            = [
                // phpcs:ignore Generic.Files.LineLength
                AddressDataRequest::POSTAL_CODE   => preg_replace('/[^0-9]/', '', (string) $billingAddress->getPostcode()),
                // phpcs:ignore Generic.Files.LineLength
                AddressDataRequest::STREET        => $this->config->getValueForAddress($billingAddress, AddressDataRequest::STREET),
                // phpcs:ignore Generic.Files.LineLength
                AddressDataRequest::NUMBER        => $this->config->getValueForAddress($billingAddress, AddressDataRequest::NUMBER),
                // phpcs:ignore Generic.Files.LineLength
                AddressDataRequest::DISTRICT      => $this->config->getValueForAddress($billingAddress, AddressDataRequest::DISTRICT),
                // phpcs:ignore Generic.Files.LineLength
                AddressDataRequest::COMPLEMENT    => $this->config->getValueForAddress($billingAddress, AddressDataRequest::COMPLEMENT),
                AddressDataRequest::LOCALITY      => $billingAddress->getCity(),
                AddressDataRequest::STATE         => $billingAddress->getRegionCode(),
                AddressDataRequest::COUNTRY_CODE  => $billingAddress->getCountryId(),
            ];
        }

        return $result;
    }
}
