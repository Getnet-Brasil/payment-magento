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
use Getnet\PaymentMagento\Gateway\Config\ConfigApplePay;
use Magento\Checkout\Model\ConfigProviderInterface;
use Magento\Framework\UrlInterface;
use Magento\Quote\Api\Data\CartInterface;

/**
 * Class ConfigProviderApplePay - Defines properties of the payment form.
 */
class ConfigProviderApplePay implements ConfigProviderInterface
{
    /**
     * @const string
     */
    public const CODE = 'getnet_paymentmagento_applepay';

    /**
     * @var ConfigApplePay
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
     * @var UrlInterface
     */
    private $urlBuilder;

    /**
     * @param ConfigApplePay $config
     * @param ConfigCc       $configCc
     * @param CartInterface  $cart
     * @param UrlInterface   $urlBuilder
     */
    public function __construct(
        ConfigApplePay $config,
        ConfigCc $configCc,
        CartInterface $cart,
        UrlInterface $urlBuilder
    ) {
        $this->config = $config;
        $this->configCc = $configCc;
        $this->cart = $cart;
        $this->urlBuilder = $urlBuilder;
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
                    'merchantName'        => $this->config->getDisplayName($storeId),
                    'buttonStyle'         => $this->config->getButtonStyle($storeId),
                    'buttonType'          => $this->config->getButtonType($storeId),
                    'supportedNetworks'   => ['masterCard', 'visa'],
                    'merchantCapabilities' => ['supports3DS'],
                    'validationUrl'       => $this->urlBuilder->getUrl('getnet/applepay/validate'),
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
