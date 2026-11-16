<?php
/**
 * Copyright © Getnet. All rights reserved.
 *
 * @author    Bruno Elisei <brunoelisei@o2ti.com>
 * See LICENSE for license details.
 */

declare(strict_types=1);

namespace Getnet\PaymentMagento\Gateway\Request\Pix\Biometric;

use Getnet\PaymentMagento\Gateway\Config\Config;
use Getnet\PaymentMagento\Gateway\SubjectReader;
use Getnet\PaymentMagento\Observer\DataAssignObserverPix;
use Magento\Framework\Exception\LocalizedException;
use Magento\Payment\Gateway\Request\BuilderInterface;
use Magento\Sales\Model\Order;

/**
 * Payer (CPF + name) of the Biometric Pix charge.
 *
 * The API does not validate the CPF on creation — an invalid one only fails inside the
 * payer journey — so it is validated here to fail fast at checkout.
 */
class CustomerDataRequest implements BuilderInterface
{
    public const CUSTOMER = 'customer';

    public const DOCUMENT_NUMBER = 'document_number';

    public const NAME = 'name';

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
     *
     * @throws LocalizedException
     *
     * @return array
     */
    public function build(array $buildSubject)
    {
        $payment = $this->subjectReader->readPayment($buildSubject)->getPayment();
        /** @var Order $order */
        $order = $payment->getOrder();

        $document = preg_replace('/\D/', '', (string) $this->getTaxDocument($payment, $order));

        if (!$this->isValidCpf($document)) {
            throw new LocalizedException(
                __('Please enter a valid CPF to pay with Pix by biometrics.')
            );
        }

        return [
            self::CUSTOMER => [
                self::DOCUMENT_NUMBER => $document,
                self::NAME            => $this->getName($payment, $order),
            ],
        ];
    }

    /**
     * Tax document informed at checkout, falling back to the customer/address one.
     *
     * @param \Magento\Payment\Model\InfoInterface $payment
     * @param Order                                $order
     *
     * @return string|null
     */
    public function getTaxDocument($payment, $order): ?string
    {
        $document = $payment->getAdditionalInformation(DataAssignObserverPix::PAYER_TAX_DOCUMENT);

        if ($document) {
            return (string) $document;
        }

        $billing = $order->getBillingAddress();

        if ($this->config->getAddtionalValue('get_tax_document_from', $order->getStoreId()) === 'address') {
            return $billing ? $billing->getVatId() : null;
        }

        return $order->getCustomerTaxvat() ?: ($billing ? $billing->getVatId() : null);
    }

    /**
     * Payer name informed at checkout, falling back to the billing name.
     *
     * @param \Magento\Payment\Model\InfoInterface $payment
     * @param Order                                $order
     *
     * @return string
     */
    public function getName($payment, $order): string
    {
        $name = (string) $payment->getAdditionalInformation(DataAssignObserverPix::PAYER_FULLNAME);

        if ($name === '') {
            $billing = $order->getBillingAddress();
            $name = $billing
                ? trim($billing->getFirstname().' '.$billing->getLastname())
                : trim($order->getCustomerFirstname().' '.$order->getCustomerLastname());
        }

        return mb_substr($name, 0, 100);
    }

    /**
     * Validate a CPF (11 digits + check digits).
     *
     * @param string $cpf
     *
     * @return bool
     */
    public function isValidCpf(string $cpf): bool
    {
        if (strlen($cpf) !== 11 || preg_match('/^(\d)\1{10}$/', $cpf)) {
            return false;
        }

        for ($digit = 9; $digit < 11; $digit++) {
            $sum = 0;
            for ($pos = 0; $pos < $digit; $pos++) {
                $sum += (int) $cpf[$pos] * (($digit + 1) - $pos);
            }
            $check = ((10 * $sum) % 11) % 10;
            if ((int) $cpf[$digit] !== $check) {
                return false;
            }
        }

        return true;
    }
}
