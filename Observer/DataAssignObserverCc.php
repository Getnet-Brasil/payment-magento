<?php
/**
 * Copyright © Getnet. All rights reserved.
 *
 * @author    Bruno Elisei <brunoelisei@o2ti.com>
 * See LICENSE for license details.
 */

namespace Getnet\PaymentMagento\Observer;

use Getnet\PaymentMagento\Gateway\Config\ConfigCc;
use Magento\Framework\Event\Observer;
use Magento\Payment\Observer\AbstractDataAssignObserver;
use Magento\Quote\Api\Data\PaymentInterface;

/**
 * Class DataAssignObserverCc - Capture credit card payment information.
 */
class DataAssignObserverCc extends AbstractDataAssignObserver
{
    /**
     * @const string
     */
    public const PAYMENT_INFO_NUMBER_TOKEN = 'cc_number_token';

    /**
     * @const string
     */
    public const PAYMENT_INFO_CC_NUMBER = 'cc_number';

    /**
     * @const string
     */
    public const PAYMENT_INFO_CC_TYPE = 'cc_type';

    /**
     * @const string
     */
    public const PAYMENT_INFO_CC_EXP_M = 'cc_exp_month';

    /**
     * @const string
     */
    public const PAYMENT_INFO_CC_EXP_Y = 'cc_exp_year';

    /**
     * @const string
     */
    public const PAYMENT_INFO_CC_INSTALLMENTS = 'cc_installments';

    /**
     * @const string
     */
    public const PAYMENT_INFO_CARDHOLDER_NAME = 'cc_cardholder_name';

    /**
     * @const string
     */
    public const PAYMENT_INFO_HOLDER_TAX_DOCUMENT = 'cc_holder_tax_document';

    /**
     * @const string
     */
    public const PAYMENT_INFO_HOLDER_PHONE = 'cc_holder_phone';

    /**
     * @const string
     */
    public const PAYMENT_INFO_CC_SAVE = 'is_active_payment_token_enabler';

    /**
     * @const string
     */
    public const PAYMENT_INFO_CC_CID = 'cc_cid';

    /**
     * @const string
     */
    public const PAYMENT_INFO_CC_PUBLIC_ID = 'cc_public_id';
    /**
     * @var array
     */
    protected $addInformationList = [
        self::PAYMENT_INFO_NUMBER_TOKEN,
        self::PAYMENT_INFO_CARDHOLDER_NAME,
        self::PAYMENT_INFO_CC_NUMBER,
        self::PAYMENT_INFO_CC_TYPE,
        self::PAYMENT_INFO_CC_CID,
        self::PAYMENT_INFO_CC_EXP_M,
        self::PAYMENT_INFO_CC_EXP_Y,
        self::PAYMENT_INFO_CC_INSTALLMENTS,
        self::PAYMENT_INFO_CC_SAVE,
        self::PAYMENT_INFO_HOLDER_TAX_DOCUMENT,
        self::PAYMENT_INFO_HOLDER_PHONE,
        self::PAYMENT_INFO_CC_PUBLIC_ID
    ];

    /**
     * @var ConfigCc
     */
    protected $config;

    /**
     * @param ConfigCc $config
     */
    public function __construct(
        ConfigCc $config
    ) {
        $this->config = $config;
    }

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
                if ($addInformationKey === self::PAYMENT_INFO_CC_TYPE) {
                    $paymentInfo->setAdditionalInformation(
                        $addInformationKey,
                        $this->getFullTypeName($additionalData[$addInformationKey])
                    );
                    continue;
                }
                if ($addInformationKey === self::PAYMENT_INFO_CC_NUMBER) {
                    $paymentInfo->setAdditionalInformation(
                        $addInformationKey,
                        'xxxx xxxx xxxx '.substr($additionalData[$addInformationKey], -4)
                    );
                    continue;
                }
                if ($additionalData[$addInformationKey]) {
                    $paymentInfo->setAdditionalInformation(
                        $addInformationKey,
                        $additionalData[$addInformationKey]
                    );
                }
            }
        }
    }

    /**
     * Get Name for Cc Type.
     *
     * @param string $type
     *
     * @return string
     */
    public function getFullTypeName(string $type): string
    {
        $mapper = $this->config->getCcTypesMapper();
        $type = array_search($type, $mapper);

        return  ucfirst($type);
    }
}
