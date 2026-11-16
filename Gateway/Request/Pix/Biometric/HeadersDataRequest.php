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
use Getnet\PaymentMagento\Gateway\Config\ConfigPix;
use Getnet\PaymentMagento\Gateway\Http\Api;
use Getnet\PaymentMagento\Gateway\SubjectReader;
use Magento\Payment\Gateway\Request\BuilderInterface;

/**
 * Seller and expiration headers of the Biometric Pix charge.
 *
 * The seller is resolved from the token, but the spec still declares x-seller-id.
 * The expiration header documented in the integration guide ("expiration-time") is
 * ignored by the API — x-biometric-expiration-time is the one honored (60..1800s).
 */
class HeadersDataRequest implements BuilderInterface
{
    public const HEADER_SELLER_ID = 'x-seller-id';

    public const HEADER_EXPIRATION = 'x-biometric-expiration-time';

    /**
     * @var SubjectReader
     */
    protected $subjectReader;

    /**
     * @var Config
     */
    protected $config;

    /**
     * @var ConfigPix
     */
    protected $configPix;

    /**
     * @param SubjectReader $subjectReader
     * @param Config        $config
     * @param ConfigPix     $configPix
     */
    public function __construct(
        SubjectReader $subjectReader,
        Config $config,
        ConfigPix $configPix
    ) {
        $this->subjectReader = $subjectReader;
        $this->config = $config;
        $this->configPix = $configPix;
    }

    /**
     * Build.
     *
     * @param array $buildSubject
     *
     * @return array
     */
    public function build(array $buildSubject)
    {
        $storeId = $this->subjectReader->readPayment($buildSubject)->getPayment()->getOrder()->getStoreId();

        return [
            Api::EXTRA_HEADERS => [
                self::HEADER_SELLER_ID  => $this->config->getMerchantGatewaySellerId($storeId),
                self::HEADER_EXPIRATION => (string) $this->configPix->getBiometricExpiration($storeId),
            ],
        ];
    }
}
