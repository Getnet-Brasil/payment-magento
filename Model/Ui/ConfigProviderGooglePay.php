<?php
/**
 * Copyright © Getnet. All rights reserved.
 *
 * @author    Bruno Elisei <brunoelisei@o2ti.com>
 * See LICENSE for license details.
 */

declare(strict_types=1);

namespace Getnet\PaymentMagento\Model\Ui;

use Getnet\PaymentMagento\Gateway\Config\ConfigCc;
use Getnet\PaymentMagento\Gateway\Config\ConfigGooglePay;
use Magento\Checkout\Model\ConfigProviderInterface;
use Magento\Quote\Api\Data\CartInterface;

/**
 * Class ConfigProviderGooglePay - Defines properties of the payment form.
 */
class ConfigProviderGooglePay implements ConfigProviderInterface
{
    /**
     * @const string
     */
    public const CODE = 'getnet_paymentmagento_googlepay';

    /**
     * @var ConfigGooglePay
     */
    private $config;

    /**
     * @var ConfigCc
     */
    private $configCc;

    /**
     * @var CartInterface
     */
    private $cart;

    /**
     * @param ConfigGooglePay $config
     * @param ConfigCc        $configCc
     * @param CartInterface   $cart
     */
    public function __construct(
        ConfigGooglePay $config,
        ConfigCc $configCc,
        CartInterface $cart
    ) {
        $this->config = $config;
        $this->configCc = $configCc;
        $this->cart = $cart;
    }

    /**
     * Retrieve assoc array of checkout configuration.
     *
     * Installment rules are shared with the credit card method (same admin settings and interest totals).
     *
     * @return array
     */
    public function getConfig()
    {
        $storeId = $this->cart->getStoreId();

        return [
            'payment' => [
                self::CODE => [
                    'isActive'            => $this->config->isActive($storeId),
                    'title'               => $this->config->getTitle($storeId),
                    'environment'         => $this->config->getGoogleEnvironment($storeId),
                    'gateway'             => ConfigGooglePay::GATEWAY,
                    'gatewayMerchantId'   => $this->config->getGatewayMerchantId($storeId),
                    'googleMerchantId'    => $this->config->getGoogleMerchantId($storeId),
                    'merchantName'        => $this->config->getMerchantName($storeId),
                    'buttonColor'         => $this->config->getButtonColor($storeId),
                    'buttonType'          => $this->config->getButtonType($storeId),
                    'allowedCardNetworks' => ['MASTERCARD', 'VISA'],
                    'allowedAuthMethods'  => ['PAN_ONLY', 'CRYPTOGRAM_3DS'],
                    'currencyCode'        => 'BRL',
                    'countryCode'         => 'BR',
                    'info_interest'       => $this->configCc->getInfoInterest($storeId),
                    'min_installment'     => $this->configCc->getMinInstallment($storeId),
                    'max_installment'     => $this->configCc->getMaxInstallment($storeId),
                ],
            ],
        ];
    }
}
