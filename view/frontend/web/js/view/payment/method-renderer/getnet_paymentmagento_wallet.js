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
    'Magento_Payment/js/model/credit-card-validation/credit-card-data',
    'mage/translate',
    'Getnet_PaymentMagento/js/view/payment/lib/jquery/jquery.mask',
    'Getnet_PaymentMagento/js/view/payment/gateway/calculate-installment'
], function (_, $, Component, walletData, $t, _mask, getnetInstallment) {
    'use strict';

    return Component.extend({
        defaults: {
            hasWalletDataFormCredit: false,
            active: false,
            template: 'Getnet_PaymentMagento/payment/wallet',
            walletForm: 'Getnet_PaymentMagento/payment/wallet-form',
            walletData: null,
            walletCardType: '',
            walletCcInstallments: '',
            walletPayerPhone: ''
        },

        /**
         * Initializes model instance.
         *
         * @returns {Object}
         */
        initObservable() {
            this._super().observe([
                'active',
                'walletData',
                'walletCardType',
                'walletCcInstallments',
                'walletPayerPhone',
                'hasWalletDataFormCredit'
            ]);
            return this;
        },

        /**
         * Get code
         * @returns {String}
         */
        getCode() {
            return 'getnet_paymentmagento_wallet';
        },

        /**
         * Get auxiliary code
         * @returns {String}
         */
        getAuxiliaryCode() {
            return 'getnet_paymentmagento_cc';
        },

        /**
         * Init component
         */
        initialize() {
            var self = this,
                phone;

            phone = $('#' + this.getCode() + '_payer_phone');

            phone.mask('+55(00)00000-0000', { clearIfNotMatch: true });

            this._super();

            self.walletCardType.subscribe(function (value) {
                walletData.walletCardType = value;
                if (value === 'credit') {
                    self.hasWalletDataFormCredit(true);
                }
                if (value === 'debit') {
                    self.hasWalletDataFormCredit(false);
                }
            });

            self.walletCcInstallments.subscribe(function (value) {
                walletData.walletCcInstallments = value;
            });

            self.walletPayerPhone.subscribe(function (value) {
                walletData.walletPayerPhone = value;
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
                    'wallet_card_type': this.walletCardType(),
                    'wallet_payer_phone': this.walletPayerPhone(),
                    'cc_installments': this.walletCcInstallments()
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
        },

        /**
         * Get Card Type
         * @returns {Array}
         */
        getWalletCardType() {
            var cardType = [];

            cardType[0] = {
                'value': 'credit',
                'cardType': $t('Credit Card')
            };
            cardType[1] = {
                'value': 'debit',
                'cardType': $t('Debit Card')
            };
            return cardType;
        },

        /**
         * Get Calculete instalments
         * @returns {Array}
         */
        getCalculeteInstallments() {
            return getnetInstallment.getInstallments();
        }
    });
});
