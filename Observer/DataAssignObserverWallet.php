<?php
/**
 * Copyright © Getnet. All rights reserved.
 *
 * @author    Bruno Elisei <brunoelisei@o2ti.com>
 * See LICENSE for license details.
 */

namespace Getnet\PaymentMagento\Observer;

use Magento\Framework\Event\Observer;
use Magento\Payment\Observer\AbstractDataAssignObserver;
use Magento\Quote\Api\Data\PaymentInterface;

/**
 * Class Data Assign Observer Wallet - Capture wallet payment information.
 */
class DataAssignObserverWallet extends AbstractDataAssignObserver
{
    /**
     * @const string
     */
    public const PAYMENT_INFO_WALLET_CARD_TYPE = 'wallet_card_type';

    /**
     * @const string
     */
    public const PAYMENT_INFO_WALLET_INSTALLMENTS = 'cc_installments';

    /**
     * @const string
     */
    public const PAYMENT_INFO_WALLET_PAYER_PHONE = 'wallet_payer_phone';

    /**
     * @var array
     */
    protected $addInformationList = [
        self::PAYMENT_INFO_WALLET_CARD_TYPE,
        self::PAYMENT_INFO_WALLET_INSTALLMENTS,
        self::PAYMENT_INFO_WALLET_PAYER_PHONE,
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
            if (isset($additionalData[$addInformationKey])) {
                if ($additionalData[$addInformationKey]) {
                    $paymentInfo->setAdditionalInformation(
                        $addInformationKey,
                        $additionalData[$addInformationKey]
                    );
                }
            }
        }
    }
}
