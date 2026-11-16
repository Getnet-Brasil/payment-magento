/**
 * Copyright © Getnet. All rights reserved.
 *
 * @author    Bruno Elisei <brunoelisei@o2ti.com>
 * See LICENSE for license details.
 */
define([
    'underscore',
    'jquery',
    'Magento_Payment/js/view/payment/cc-form',
    'Magento_Checkout/js/model/quote',
    'Getnet_PaymentMagento/js/view/payment/lib/jquery/jquery.mask',
    'Magento_Payment/js/model/credit-card-validation/credit-card-data'
], function (_, $, Component, quote, _mask, pixData) {
    'use strict';

    var MODE_BIOMETRIC = 'biometric',
        MODE_QRCODE = 'qrcode';

    return Component.extend({
        defaults: {
            active: false,
            template: 'Getnet_PaymentMagento/payment/pix',
            pixForm: 'Getnet_PaymentMagento/payment/pix-form',
            pixData: null,
            payerFullName: '',
            payerTaxDocument: '',
            pixMode: MODE_QRCODE
        },

        initObservable() {
            this._super().observe(['active', 'pixData', 'payerFullName', 'payerTaxDocument', 'pixMode']);
            return this;
        },

        getCode() {
            return 'getnet_paymentmagento_pix';
        },

        initialize() {
            var self = this;

            this._super();

            if (self.isBiometricActive()) {
                self.pixMode(self.getBiometricConfig().default === MODE_QRCODE ? MODE_QRCODE : MODE_BIOMETRIC);
            }

            self.payerFullName.subscribe(function (value) {
                pixData.payerFullName = value;
            });

            self.payerTaxDocument.subscribe(function (value) {
                $('#' + self.getCode() + '_payer_tax_document').mask('000.000.000-00', { clearIfNotMatch: true });
                pixData.payerTaxDocument = value;
            });

            // Prefill the CPF with the billing VAT number when the payer has not typed one
            quote.billingAddress.subscribe(function (address) {
                if (address && address.vatId && !self.payerTaxDocument()) {
                    self.payerTaxDocument(String(address.vatId));
                }
            });
        },

        isActive() {
            var active = this.getCode() === this.isChecked();

            this.active(active);
            return active;
        },

        initFormElement(element) {
            this.formElement = element;
            $(this.formElement).validation();
        },

        beforePlaceOrder() {
            if (!$(this.formElement).valid()) {
                return;
            }
            this.placeOrder();
        },

        /**
         * After the order: the biometric flow continues on the bank journey (top-level navigation —
         * the passkey is bound to the journey domain, so it cannot run in an iframe).
         */
        afterPlaceOrder() {
            if (this.isBiometricMode()) {
                this.redirectAfterPlaceOrder = false;
                window.location.replace(this.getBiometricConfig().redirect_url);
            }
        },

        getData() {
            return {
                method: this.getCode(),
                'additional_data': {
                    'pix_mode': this.isBiometricMode() ? MODE_BIOMETRIC : MODE_QRCODE,
                    'pix_payer_fullname': this.payerFullName(),
                    'pix_payer_tax_document': this.isBiometricMode() ? this.payerTaxDocument() : ''
                }
            };
        },

        getBiometricConfig() {
            return window.checkoutConfig.payment[this.getCode()].biometric || {};
        },

        isBiometricActive() {
            return Boolean(this.getBiometricConfig().active);
        },

        isBiometricMode() {
            return this.isBiometricActive() && this.pixMode() === MODE_BIOMETRIC;
        },

        getBiometricInstruction() {
            return this.getBiometricConfig().instruction;
        },

        getTitle() {
            return window.checkoutConfig.payment[this.getCode()].title;
        },

        getLogo() {
            return window.checkoutConfig.payment[this.getCode()].logo;
        },

        isShowLegend() {
            return true;
        },

        getInstructionCheckout() {
            return window.checkoutConfig.payment[this.getCode()].instruction_checkout;
        }
    });
});
