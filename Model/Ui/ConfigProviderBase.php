<?php
/**
 * Copyright © Getnet. All rights reserved.
 *
 * @author    Bruno Elisei <brunoelisei@o2ti.com>
 * See LICENSE for license details.
 */

namespace Getnet\PaymentMagento\Model\Ui;

use Getnet\PaymentMagento\Gateway\Config\Config;
use Magento\Checkout\Model\ConfigProviderInterface;
use Magento\Payment\Model\CcConfig;
use Magento\Quote\Api\Data\CartInterface;

/**
 * Class ConfigProviderBase - Defines properties of the payment form.
 */
class ConfigProviderBase implements ConfigProviderInterface
{
    /*
     * @const string
     */
    public const CODE = 'getnet_paymentmagento';

    /*
     * @var METHOD CODE CC
     */
    public const METHOD_CODE_CC = 'getnet_paymentmagento_cc';

    /*
     * @var METHOD CODE CC VAULT
     */
    public const METHOD_CODE_CC_VAULT = 'getnet_paymentmagento_cc_vault';

    /*
     * @var METHOD CODE BOLETO
     */
    public const METHOD_CODE_BOLETO = 'getnet_paymentmagento_boleto';

    /**
     * @var Config
     */
    private $config;

    /**
     * @var CartInterface
     */
    private $cart;

    /**
     * @var CcConfig
     */
    protected $ccConfig;

    /**
     * @param Config        $config
     * @param CartInterface $cart
     * @param CcConfig      $ccConfig
     */
    public function __construct(
        Config $config,
        CartInterface $cart,
        CcConfig $ccConfig
    ) {
        $this->config = $config;
        $this->cart = $cart;
        $this->ccConfig = $ccConfig;
    }

    /**
     * Retrieve assoc array of checkout configuration.
     *
     * @return array
     */
    public function getConfig()
    {
        return [
            'payment' => [
                Config::METHOD => [
                    'isActive' => false,
                ],
            ],
        ];
    }
}
