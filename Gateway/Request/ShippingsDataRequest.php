<?php
/**
 * Copyright © Getnet. All rights reserved.
 *
 * @author    Bruno Elisei <brunoelisei@o2ti.com>
 * See LICENSE for license details.
 */

namespace Getnet\PaymentMagento\Gateway\Request;

use Getnet\PaymentMagento\Gateway\Config\Config;
use Getnet\PaymentMagento\Gateway\Data\Order\OrderAdapterFactory;
use Getnet\PaymentMagento\Gateway\SubjectReader;
use InvalidArgumentException;
use Magento\Payment\Gateway\Data\PaymentDataObjectInterface;
use Magento\Payment\Gateway\Request\BuilderInterface;

/**
 * Class Shippings Data Builder - Shippings structure.
 */
class ShippingsDataRequest implements BuilderInterface
{
    /**
     * Shipping block name.
     */
    public const SHIPPINGS = 'shippings';

    /**
     * Address block name.
     */
    public const ADDRESS = 'address';

    /**
     * The first name value must be less than or equal to 255 characters.
     * Required.
     */
    public const FIRST_NAME = 'first_name';

    /**
     * The full name value must be less than or equal to 255 characters.
     * Required.
     */
    public const NAME = 'name';

    /**
     * The customer’s email address.
     * Required.
     */
    public const EMAIL = 'email';

    /**
     * Phone Number - block name.
     */
    public const PHONE_NUMBER = 'phone_number';

    /**
     * Shipping Amount block name.
     */
    public const SHIPPING_AMOUNT = 'shipping_amount';
    /**
     * @var SubjectReader
     */
    private $subjectReader;

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

        $shippingAddress = $orderAdapter->getShippingAddress();
        if ($shippingAddress) {
            $name = $shippingAddress->getFirstname().' '.$shippingAddress->getLastname();

            $result[self::SHIPPINGS][] = [
                self::FIRST_NAME        => $shippingAddress->getFirstname(),
                self::NAME              => $name,
                self::EMAIL             => $shippingAddress->getEmail(),
                self::PHONE_NUMBER      => preg_replace('/[^0-9]/', '', $shippingAddress->getTelephone()),
                self::SHIPPING_AMOUNT   => $this->config->formatPrice(
                    $orderAdapter->getShippingAmount()
                ),
                self::ADDRESS           => [
                    AddressDataRequest::POSTAL_CODE   => preg_replace('/[^0-9]/', '', $shippingAddress->getPostcode()),
                    // phpcs:ignore Generic.Files.LineLength
                    AddressDataRequest::STREET        => $this->config->getValueForAddress($shippingAddress, AddressDataRequest::STREET),
                    // phpcs:ignore Generic.Files.LineLength
                    AddressDataRequest::NUMBER        => $this->config->getValueForAddress($shippingAddress, AddressDataRequest::NUMBER),
                    // phpcs:ignore Generic.Files.LineLength
                    AddressDataRequest::DISTRICT      => $this->config->getValueForAddress($shippingAddress, AddressDataRequest::DISTRICT),
                    // phpcs:ignore Generic.Files.LineLength
                    AddressDataRequest::COMPLEMENT    => $this->config->getValueForAddress($shippingAddress, AddressDataRequest::COMPLEMENT),
                    AddressDataRequest::LOCALITY      => $shippingAddress->getCity(),
                    AddressDataRequest::STATE         => $shippingAddress->getRegionCode(),
                    AddressDataRequest::COUNTRY_CODE  => $shippingAddress->getCountryId(),
                ],
            ];
        }

        return $result;
    }
}
