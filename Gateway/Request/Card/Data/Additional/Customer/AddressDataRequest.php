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

/**
 * Class Address Data Request - Address structure.
 */
class AddressDataRequest implements BuilderInterface
{
    /**
     * Billing Address block name.
     */
    public const BILLING_ADDRESS = 'billing_address';

    /**
     * Shipping block name.
     */
    public const SHIPPING = 'shipping';

    /**
     * Address block name.
     */
    public const ADDRESS = 'address';

    /**
     * The street address. Maximum 255 characters
     * Required.
     */
    public const STREET = 'street';

    /**
     * The number. 1 or 10 alphanumeric digits
     * Required.
     */
    public const NUMBER = 'number';

    /**
     * The complement address. Maximum 255 characters
     * Required.
     */
    public const COMPLEMENT = 'complement';

    /**
     * The district address. Maximum 255 characters
     * Required.
     */
    public const DISTRICT = 'district';

    /**
     * The locality/city. 255 character maximum.
     * Required.
     */
    public const LOCALITY = 'city';

    /**
     * The state or province. The region must be a 2-letter abbreviation.
     * Required.
     */
    public const STATE = 'state';

    /**
     * The ISO 3166-1 alpha-3.
     * Required.
     */
    public const COUNTRY_CODE = 'country';

    /**
     * The postal code.
     * Required.
     */
    public const POSTAL_CODE = 'postal_code';

    /**
     * The type address.
     * Required.
     */
    public const TYPE = 'type';

    /**
     * @var SubjectReader
     */
    protected $subjectReader;

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
     * @param Config              $config
     * @param OrderAdapterFactory $orderAdapterFactory
     */
    public function __construct(
        SubjectReader $subjectReader,
        Config $config,
        OrderAdapterFactory $orderAdapterFactory
    ) {
        $this->subjectReader = $subjectReader;
        $this->config = $config;
        $this->orderAdapterFactory = $orderAdapterFactory;
    }

    /**
     * Build.
     *
     * @param array $buildSubject
     */
    public function build(array $buildSubject): array
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
            $result[CardInitSchemaDataRequest::DATA]
            [AdditionalInitSchemaDataRequest::ADDITIONAL_DATA]
            [CustomerDataRequest::CUSTOMER][self::BILLING_ADDRESS] = [
                    self::POSTAL_CODE       => $billingAddress->getPostcode(),
                    self::STREET            => $this->config->getValueForAddress($billingAddress, self::STREET),
                    self::NUMBER            => $this->config->getValueForAddress($billingAddress, self::NUMBER),
                    self::DISTRICT          => $this->config->getValueForAddress($billingAddress, self::DISTRICT),
                    self::COMPLEMENT        => $this->config->getValueForAddress($billingAddress, self::COMPLEMENT),
                    self::LOCALITY          => $billingAddress->getCity(),
                    self::STATE             => $billingAddress->getRegionCode(),
                    self::COUNTRY_CODE      => 'BRA',
                    self::TYPE              => 'COBRANCA',
                ];
        }

        $shippingAddress = $orderAdapter->getShippingAddress();
        if ($shippingAddress) {
            $result[CardInitSchemaDataRequest::DATA]
            [AdditionalInitSchemaDataRequest::ADDITIONAL_DATA]
            [CustomerDataRequest::CUSTOMER]
            [self::SHIPPING][self::ADDRESS] = [
                    self::POSTAL_CODE       => $shippingAddress->getPostcode(),
                    self::STREET            => $this->config->getValueForAddress($shippingAddress, self::STREET),
                    self::NUMBER            => $this->config->getValueForAddress($shippingAddress, self::NUMBER),
                    self::DISTRICT          => $this->config->getValueForAddress($shippingAddress, self::DISTRICT),
                    self::COMPLEMENT        => $this->config->getValueForAddress($shippingAddress, self::COMPLEMENT),
                    self::LOCALITY          => $shippingAddress->getCity(),
                    self::STATE             => $shippingAddress->getRegionCode(),
                    self::COUNTRY_CODE      => 'BRA',
                ];
        }

        return $result;
    }
}
