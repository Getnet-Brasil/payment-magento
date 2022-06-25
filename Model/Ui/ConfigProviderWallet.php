<?php
/**
 * Copyright © Getnet. All rights reserved.
 *
 * @author    Bruno Elisei <brunoelisei@o2ti.com>
 * See LICENSE for license details.
 */

namespace Getnet\PaymentMagento\Model\Ui;

use Getnet\PaymentMagento\Gateway\Config\ConfigWallet;
use Magento\Checkout\Model\ConfigProviderInterface;
use Magento\Framework\Escaper;
use Magento\Framework\View\Asset\Source;
use Magento\Payment\Model\CcConfig;
use Magento\Quote\Api\Data\CartInterface;

/**
 * Class ConfigProviderWallet - Defines properties of the payment form..
 */
class ConfigProviderWallet implements ConfigProviderInterface
{
    /*
     * @const string
     */
    public const CODE = 'getnet_paymentmagento_wallet';

    /**
     * @var ConfigWallet
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
     * @var Escaper
     */
    protected $escaper;

    /**
     * @var Source
     */
    protected $assetSource;

    /**
     * @param ConfigWallet  $config
     * @param CartInterface $cart
     * @param CcConfig      $ccConfig
     * @param Escaper       $escaper
     * @param Source        $assetSource
     */
    public function __construct(
        ConfigWallet $config,
        CartInterface $cart,
        CcConfig $ccConfig,
        Escaper $escaper,
        Source $assetSource
    ) {
        $this->config = $config;
        $this->cart = $cart;
        $this->escaper = $escaper;
        $this->ccConfig = $ccConfig;
        $this->assetSource = $assetSource;
    }

    /**
     * Retrieve assoc array of checkout configuration.
     *
     * @return array
     */
    public function getConfig()
    {
        $storeId = $this->cart->getStoreId();

        return [
            'payment' => [
                self::CODE => [
                    'isActive'             => $this->config->isActive($storeId),
                    'title'                => $this->config->getTitle($storeId),
                    'instruction_checkout' => nl2br(
                        $this->escaper->escapeHtml(
                            $this->config->getInstructionCheckout($storeId)
                        )
                    ),
                    'logo'                 => $this->getLogo(),
                ],
            ],
        ];
    }

    /**
     * Get icons for available payment methods.
     *
     * @return array
     */
    public function getLogo()
    {
        $logo = [];
        $asset = $this->ccConfig->createAsset('Getnet_PaymentMagento::images/wallet/logo.svg');
        $placeholder = $this->assetSource->findSource($asset);
        if ($placeholder) {
            list($width, $height) = getimagesizefromstring($asset->getSourceFile());
            $logo = [
                'url'    => $asset->getUrl(),
                'width'  => $width,
                'height' => $height,
                'title'  => __('Getnet'),
            ];
        }

        return $logo;
    }
}
