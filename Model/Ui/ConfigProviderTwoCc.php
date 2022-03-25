<?php
/**
 * Copyright © Getnet. All rights reserved.
 *
 * @author    Bruno Elisei <brunoelisei@o2ti.com>
 * See LICENSE for license details.
 */

namespace Getnet\PaymentMagento\Model\Ui;

use Getnet\PaymentMagento\Gateway\Config\Config as ConfigBase;
use Getnet\PaymentMagento\Gateway\Config\ConfigCc;
use Getnet\PaymentMagento\Gateway\Config\ConfigTwoCc;
use Magento\Checkout\Model\ConfigProviderInterface;
use Magento\Framework\Session\SessionManager;
use Magento\Framework\View\Asset\Source;
use Magento\Payment\Model\CcConfig;
use Magento\Quote\Api\Data\CartInterface;

/**
 * Class ConfigProviderTwoCc - Defines properties of the payment form.
 *
 * @SuppressWarnings(PHPCPD)
 */
class ConfigProviderTwoCc implements ConfigProviderInterface
{
    /*
     * @const string
     */
    public const CODE = 'getnet_paymentmagento_two_cc';

    /*
     * @const string
     */
    public const VAULT_CODE = 'getnet_paymentmagento_cc_vault';

    /**
     * @var ConfigBase
     */
    protected $configBase;

    /**
     * @var ConfigCc
     */
    protected $configCc;

    /**
     * @var configTwoCc
     */
    protected $configTwoCc;

    /**
     * @var CartInterface
     */
    protected $cart;

    /**
     * @var array
     */
    protected $icons = [];

    /**
     * @var CcConfig
     */
    protected $ccConfig;

    /**
     * @var Source
     */
    protected $assetSource;

    /**
     * @var SessionManager
     */
    protected $session;

    /**
     * @param ConfigBase     $configBase
     * @param ConfigCc       $configCc
     * @param ConfigTwoCc    $configTwoCc
     * @param CartInterface  $cart
     * @param CcConfig       $ccConfig
     * @param Source         $assetSource
     * @param SessionManager $session
     */
    public function __construct(
        ConfigBase $configBase,
        ConfigCc $configCc,
        ConfigTwoCc $configTwoCc,
        CartInterface $cart,
        CcConfig $ccConfig,
        Source $assetSource,
        SessionManager $session
    ) {
        $this->configBase = $configBase;
        $this->configCc = $configCc;
        $this->configTwoCc = $configTwoCc;
        $this->cart = $cart;
        $this->ccConfig = $ccConfig;
        $this->assetSource = $assetSource;
        $this->session = $session;
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
                ConfigTwoCc::METHOD => [
                    'isActive'             => $this->configTwoCc->isActive($storeId),
                    'title'                => $this->configTwoCc->getTitle($storeId),
                    'useCvv'               => $this->configCc->isCvvEnabled($storeId),
                    'ccTypesMapper'        => $this->configCc->getCcTypesMapper($storeId),
                    'logo'                 => $this->getLogo(),
                    'icons'                => $this->getIcons(),
                    'tax_document_capture' => $this->configCc->hasUseTaxDocumentCapture($storeId),
                    'phone_capture'        => $this->configCc->hasUsePhoneCapture($storeId),
                    'fraud_manager'        => $this->configCc->hasUseFraudManager($storeId),
                    'info_interest'        => $this->configCc->getInfoInterest($storeId),
                    'min_installment'      => $this->configCc->getMinInstallment($storeId),
                    'max_installment'      => $this->configCc->getMaxInstallment($storeId),
                    'fingerPrintSessionId' => $this->session->getSessionId(),
                    'fingerPrintCode'      => $this->configBase->getMerchantGatewayOnlineMetrixCode($storeId),
                ],
            ],
        ];
    }

    /**
     * Get icons for available payment methods.
     *
     * @return array
     */
    public function getIcons()
    {
        if (!empty($this->icons)) {
            return $this->icons;
        }
        $storeId = $this->cart->getStoreId();
        $ccTypes = $this->configCc->getCcAvailableTypes($storeId);
        $types = explode(',', $ccTypes);
        foreach ($types as $code => $label) {
            if (!array_key_exists($code, $this->icons)) {
                $asset = $this->ccConfig->createAsset('Getnet_PaymentMagento::images/cc/'.strtolower($label).'.svg');
                $placeholder = $this->assetSource->findSource($asset);
                if ($placeholder) {
                    list($width, $height) = getimagesizefromstring($asset->getSourceFile());
                    $this->icons[$label] = [
                        'url'    => $asset->getUrl(),
                        'width'  => $width,
                        'height' => $height,
                        'title'  => __($label),
                    ];
                }
            }
        }

        return $this->icons;
    }

    /**
     * Get icons for available payment methods.
     *
     * @return array
     */
    public function getLogo()
    {
        $logo = [];
        $asset = $this->ccConfig->createAsset('Getnet_PaymentMagento::images/cc/logo.svg');
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
