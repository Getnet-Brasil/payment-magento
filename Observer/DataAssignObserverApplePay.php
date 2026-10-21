<?php
/**
 * Copyright © Getnet. All rights reserved.
 *
 * @author    Bruno Elisei <brunoelisei@o2ti.com>
 * See LICENSE for license details.
 */

declare(strict_types=1);

namespace Getnet\PaymentMagento\Observer;

use Magento\Framework\Event\Observer;
use Magento\Payment\Observer\AbstractDataAssignObserver;
use Magento\Quote\Api\Data\PaymentInterface;

/**
 * Class DataAssignObserverApplePay - Capture Apple Pay payment information.
 */
class DataAssignObserverApplePay extends AbstractDataAssignObserver
{
    /**
     * Raw token returned by Apple Pay (payment.token, JSON encoded).
     *
     * @const string
     */
    public const PAYMENT_INFO_TOKEN = 'applepay_token';

    /**
     * @const string
     */
    public const PAYMENT_INFO_INSTALLMENTS = 'cc_installments';

    /**
     * @const string
     */
    public const PAYMENT_INFO_CC_TYPE = 'cc_type';

    /**
     * @const string
     */
    public const PAYMENT_INFO_CC_NUMBER = 'cc_number';

    /**
     * @var array
     */
    protected $addInformationList = [
        self::PAYMENT_INFO_TOKEN,
        self::PAYMENT_INFO_INSTALLMENTS,
        self::PAYMENT_INFO_CC_TYPE,
        self::PAYMENT_INFO_CC_NUMBER,
    ];

    /**
     * Apple Pay card networks => keys of the card type mapper (cctypes_mapper).
     *
     * @var array
     */
    protected $cardNetworkMap = [
        'MASTERCARD' => 'Mastercard',
        'VISA' => 'Visa',
    ];

    /**
     * Execute.
     *
     * @param Observer $observer
     *
     * @return void
     */
    public function execute(Observer $observer)
    {
        $data = $this->readDataArgument($observer);

        $additionalData = $data->getData(PaymentInterface::KEY_ADDITIONAL_DATA);

        if (!is_array($additionalData)) {
            return;
        }

        $paymentInfo = $this->readPaymentModelArgument($observer);

        foreach ($this->addInformationList as $addInformationKey) {
            if (!isset($additionalData[$addInformationKey]) || !$additionalData[$addInformationKey]) {
                continue;
            }

            $value = $additionalData[$addInformationKey];

            if ($addInformationKey === self::PAYMENT_INFO_CC_NUMBER) {
                // Only the masked last digits are kept, never a full number.
                $value = 'xxxx xxxx xxxx '.substr((string) $value, -4);
            }

            if ($addInformationKey === self::PAYMENT_INFO_CC_TYPE) {
                $value = $this->cardNetworkMap[strtoupper((string) $value)] ?? $value;
            }

            $paymentInfo->setAdditionalInformation($addInformationKey, $value);
        }
    }
}
