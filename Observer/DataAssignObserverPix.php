<?php
/**
 * Copyright © Getnet. All rights reserved.
 *
 * @author    Bruno Elisei <brunoelisei@o2ti.com>
 * See LICENSE for license details.
 */

declare(strict_types=1);

namespace Getnet\PaymentMagento\Observer;

use Getnet\PaymentMagento\Gateway\Config\ConfigPix;
use Magento\Framework\Event\Observer;
use Magento\Payment\Observer\AbstractDataAssignObserver;
use Magento\Quote\Api\Data\PaymentInterface;

/**
 * Assign the Pix checkout data (flow and payer) to the payment.
 */
class DataAssignObserverPix extends AbstractDataAssignObserver
{
    public const PAYER_FULLNAME = 'pix_payer_fullname';

    public const PAYER_TAX_DOCUMENT = 'pix_payer_tax_document';

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

        // Always overwrite the flow: the payer may switch between QR code and biometrics
        $mode = ($additionalData[ConfigPix::MODE] ?? null) === ConfigPix::MODE_BIOMETRIC
            ? ConfigPix::MODE_BIOMETRIC
            : ConfigPix::MODE_QRCODE;
        $paymentInfo->setAdditionalInformation(ConfigPix::MODE, $mode);

        foreach ([self::PAYER_FULLNAME, self::PAYER_TAX_DOCUMENT] as $key) {
            if (!empty($additionalData[$key]) && is_scalar($additionalData[$key])) {
                $paymentInfo->setAdditionalInformation($key, trim((string) $additionalData[$key]));
            }
        }
    }
}
