<?php
/**
 * Copyright © Getnet. All rights reserved.
 *
 * @author    Bruno Elisei <brunoelisei@o2ti.com>
 * See LICENSE for license details.
 */

namespace Getnet\PaymentMagento\Gateway\Request\Card\Data\Additional\Customer;

use Getnet\PaymentMagento\Gateway\Data\Order\OrderAdapterFactory;
use Getnet\PaymentMagento\Gateway\Request\Card\CardInitSchemaDataRequest;
use Getnet\PaymentMagento\Gateway\Request\Card\Data\Additional\AdditionalInitSchemaDataRequest;
use Getnet\PaymentMagento\Gateway\SubjectReader;
use InvalidArgumentException;
use Magento\Payment\Gateway\Data\PaymentDataObjectInterface;
use Magento\Payment\Gateway\Request\BuilderInterface;

/**
 * Class Customer Data Request - Customer structure.
 */
class CustomerDataRequest implements BuilderInterface
{
    /**
     * Customer block name.
     */
    public const CUSTOMER = 'customer';

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
     * Phone Number block name.
     */
    public const PHONE_NUMBER = 'phone_number';

    /**
     * @var SubjectReader
     */
    protected $subjectReader;

    /**
     * @var OrderAdapterFactory
     */
    protected $orderAdapterFactory;

    /**
     * @param SubjectReader       $subjectReader
     * @param OrderAdapterFactory $orderAdapterFactory
     */
    public function __construct(
        SubjectReader $subjectReader,
        OrderAdapterFactory $orderAdapterFactory
    ) {
        $this->subjectReader = $subjectReader;
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
        $payment = $paymentDO->getPayment();
        $result = [];

        /** @var OrderAdapterFactory $orderAdapter * */
        $orderAdapter = $this->orderAdapterFactory->create(
            ['order' => $payment->getOrder()]
        );

        $billingAddress = $orderAdapter->getBillingAddress();

        $name = $billingAddress->getFirstname().' '.$billingAddress->getLastname();
        $result[CardInitSchemaDataRequest::DATA][AdditionalInitSchemaDataRequest::ADDITIONAL_DATA][self::CUSTOMER] = [
            self::EMAIL         => $billingAddress->getEmail(),
            self::NAME          => $name,
            self::PHONE_NUMBER  => preg_replace('/[^0-9]/', '', (string) $billingAddress->getTelephone()),
        ];

        return $result;
    }
}
