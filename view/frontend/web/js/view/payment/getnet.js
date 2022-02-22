/**
 * Copyright © Getnet. All rights reserved.
 *
 * @author    Bruno Elisei <brunoelisei@o2ti.com>
 * See LICENSE for license details.
 */
define(
    [
        'uiComponent',
        'Magento_Checkout/js/model/payment/renderer-list'
    ],
    function (
        Component,
        rendererList
    ) {
        'use strict';
        var config = window.checkoutConfig.payment,
            methodBoleto = 'getnet_paymentmagento_boleto',
            methodCc = 'getnet_paymentmagento_cc',
            methodPix = 'getnet_paymentmagento_pix',
            methodWallet = 'getnet_paymentmagento_wallet';

        if (config[methodBoleto].isActive) {
            rendererList.push(
                {
                    type: methodBoleto,
                    component: 'Getnet_PaymentMagento/js/view/payment/method-renderer/getnet_paymentmagento_boleto'
                }
            );
        }

        if (config[methodCc].isActive) {
            rendererList.push(
                {
                    type: methodCc,
                    component: 'Getnet_PaymentMagento/js/view/payment/method-renderer/getnet_paymentmagento_cc'
                }
            );
        }

        if (config[methodPix].isActive) {
            rendererList.push(
                {
                    type: methodPix,
                    component: 'Getnet_PaymentMagento/js/view/payment/method-renderer/getnet_paymentmagento_pix'
                }
            );
        }

        if (config[methodWallet].isActive) {
            rendererList.push(
                {
                    type: methodWallet,
                    component: 'Getnet_PaymentMagento/js/view/payment/method-renderer/getnet_paymentmagento_wallet'
                }
            );
        }

        /** Add view logic here if needed */
        return Component.extend({});
    }
);
