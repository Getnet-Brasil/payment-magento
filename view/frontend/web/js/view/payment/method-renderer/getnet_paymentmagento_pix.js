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
], function (_, $, Component, mask, pixData) {
    'use strict';

    return Component.extend({
        defaults: {
            active: false,
            template: 'Getnet_PaymentMagento/payment/pix',
            pixForm: 'Getnet_PaymentMagento/payment/pix-form',
            pixData: null,
            payerFullName: '',
            payerTaxDocument: ''
        },

        /**
         * Initializes model instance.
         *
         * @returns {Object}
         */
        initObservable() {
            this._super().observe(['active', 'pixData', 'payerFullName', 'payerTaxDocument']);
            return this;
        },

        /**
         * Get code
         * @returns {String}
         */
        getCode() {
            return 'getnet_paymentmagento_pix';
        },

        /**
         * Init component
         */
        initialize() {
            var self = this,
                vat = $('#getnet_paymentmagento_pix_payer_tax_document'),
                typeMaskVat;

            this._super();

            self.payerFullName.subscribe(function (value) {
                pixData.payerFullName = value;
            });

            self.payerTaxDocument.subscribe(function (value) {
                typeMaskVat = value.replace(/\D/g, '').length <= 11 ? '000.000.000-009' : '00.000.000/0000-00';
                vat.mask(typeMaskVat, { clearIfNotMatch: true });
                pixData.payerTaxDocument = value;
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
                    'pix_payer_fullname': this.payerFullName(),
                    'pix_payer_tax_document': this.payerTaxDocument()
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
         * Get instruction checkout
         * @returns {string}
         */
        getInstructionCheckout() {
            return window.checkoutConfig.payment[this.getCode()].instruction_checkout;
        }
    });
});
