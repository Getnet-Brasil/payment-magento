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
    'Getnet_PaymentMagento/js/view/payment/lib/jquery/jquery.mask',
    'Magento_Payment/js/model/credit-card-validation/credit-card-data'
], function (_, $, Component, mask, boletoData) {
    'use strict';

    return Component.extend({
        defaults: {
            active: false,
            template: 'Getnet_PaymentMagento/payment/boleto',
            boletoForm: 'Getnet_PaymentMagento/payment/boleto-form',
            boletoData: null,
            payerFullName: '',
            payerTaxDocument: ''
        },

        /**
         * Initializes model instance.
         *
         * @returns {Object}
         */
        initObservable() {
            this._super().observe(['active', 'boletoData', 'payerFullName', 'payerTaxDocument']);
            return this;
        },

        /**
         * Get code
         * @returns {String}
         */
        getCode() {
            return 'getnet_paymentmagento_boleto';
        },

        /**
         * Init component
         */
        initialize() {
            var self = this,
                vat = $('#getnet_paymentmagento_boleto_payer_tax_document'),
                typeMaskVat;

            this._super();

            self.payerFullName.subscribe(function (value) {
                boletoData.payerFullName = value;
            });

            self.payerTaxDocument.subscribe(function (value) {
                typeMaskVat = value.replace(/\D/g, '').length <= 11 ? '000.000.000-009' : '00.000.000/0000-00';
                vat.mask(typeMaskVat, { clearIfNotMatch: true });
                boletoData.payerTaxDocument = value;
            });

        },

        /**
         * Is Active
         * @returns {Boolean}
         */
        isActive() {
            var active = this.getCode() === this.isChecked();

            this.active(active);
            return active;
        },

        /**
         * Init Form Element
         * @returns {void}
         */
        initFormElement(element) {
            this.formElement = element;
            $(this.formElement).validation();
        },

        /**
         * Before Place Order
         * @returns {void}
         */
        beforePlaceOrder() {
            if (!$(this.formElement).valid()) {
                return;
            }
            this.placeOrder();
        },

        /**
         * Get data
         * @returns {Object}
         */
        getData() {
            return {
                method: this.getCode(),
                'additional_data': {
                    'boleto_payer_fullname': this.payerFullName(),
                    'boleto_payer_tax_document': this.payerTaxDocument()
                }
            };
        },

        /**
         * Get title
         * @returns {string}
         */
        getTitle() {
            return window.checkoutConfig.payment[this.getCode()].title;
        },

        /**
         * Get logo
         * @returns {string}
         */
        getLogo() {
            return window.checkoutConfig.payment[this.getCode()].logo;
        },

        /**
         * Is show legend
         * @returns {boolean}
         */
        isShowLegend() {
            return true;
        },

        /**
         * Is name capture
         * @returns {boolean}
         */
        NameCapture() {
            return window.checkoutConfig.payment[this.getCode()].name_capture;
        },

        /**
         * Is tax document capture
         * @returns {boolean}
         */
        TaxDocumentCapture() {
            return window.checkoutConfig.payment[this.getCode()].tax_document_capture;
        },

        /**
         * Get instruction checkout
         * @returns {string}
         */
        getInstructionCheckout() {
            return window.checkoutConfig.payment[this.getCode()].instruction_checkout;
        },

        /**
         * Get Expiration
         * @returns {string}
         */
        getExpiration() {
            return window.checkoutConfig.payment[this.getCode()].expiration;
        }
    });
});
