<?php
/**
 * Copyright © Getnet. All rights reserved.
 *
 * @author    Bruno Elisei <brunoelisei@o2ti.com>
 * See LICENSE for license details.
 */

namespace Getnet\PaymentMagento\Gateway\Request\V1\TwoCc;

use Getnet\PaymentMagento\Gateway\Config\Config;
use Getnet\PaymentMagento\Gateway\Config\ConfigCc;
use Getnet\PaymentMagento\Gateway\Data\Order\OrderAdapterFactory;
use Getnet\PaymentMagento\Gateway\SubjectReader;
use InvalidArgumentException;
use Magento\Payment\Gateway\Data\PaymentDataObjectInterface;
use Magento\Payment\Gateway\Request\BuilderInterface;

/**
 * Class Amount And Two Interest Data Request - Payment amount structure.
 */
class AmountAndTwoInterestDataRequest implements BuilderInterface
{
    /**
     * Amount block name.
     */
    public const AMOUNT = 'amount';

    /**
     * First card amount without interest, as typed at checkout.
     */
    public const FIRST_BASE_AMOUNT = 'cc_payment_first_base_amount';

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
     * @var ConfigCc
     */
    protected $configCc;

    /**
     * @param SubjectReader       $subjectReader
     * @param OrderAdapterFactory $orderAdapterFactory
     * @param Config              $config
     * @param ConfigCc            $configCc
     */
    public function __construct(
        SubjectReader $subjectReader,
        OrderAdapterFactory $orderAdapterFactory,
        Config $config,
        ConfigCc $configCc
    ) {
        $this->subjectReader = $subjectReader;
        $this->orderAdapterFactory = $orderAdapterFactory;
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

        // Read from the sales order: third-party OrderAdapter preferences
        // (e.g. PayPal Braintree) break the payment DO adapter return types
        $grandTotal = $paymentDO->getPayment()->getOrder()->getBaseGrandTotal();

        $payment = $paymentDO->getPayment();

        $installment = (int) ($payment->getAdditionalInformation('cc_installments') ?: 1);

        // Keep the amount typed by the shopper: cc_payment_first_amount is rewritten below with the interest,
        // and this builder runs again on later operations (accept).
        $totalFirst = $payment->getAdditionalInformation(self::FIRST_BASE_AMOUNT);
        if ($totalFirst === null) {
            $totalFirst = (float) $payment->getAdditionalInformation('cc_payment_first_amount');
            $payment->setAdditionalInformation(self::FIRST_BASE_AMOUNT, $totalFirst);
        }

        // The grand total already carries the interest of both cards (validated by the installment guard),
        // so the second card takes the rest; adding its interest again would charge more than the order.
        $interestFirst = $this->configCc->getInterestToAmount($installment, (float) $totalFirst, $order->getStoreId());
        $amountFirst = round((float) $totalFirst + $interestFirst, 2);
        $amountSecondary = round((float) $grandTotal - $amountFirst, 2);

        $result[self::AMOUNT] = $this->config->formatPrice(round($amountFirst + $amountSecondary, 2));

        $payment->setAdditionalInformation(
            'cc_payment_secondary_amount',
            round($amountSecondary, 2)
        );

        $payment->setAdditionalInformation(
            'cc_payment_first_amount',
            round($amountFirst, 2)
        );

        return $result;
    }
}
