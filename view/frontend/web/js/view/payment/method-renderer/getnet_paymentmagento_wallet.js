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
    'Magento_Payment/js/model/credit-card-validation/credit-card-data',
    'Magento_Checkout/js/model/quote',
    'Magento_Catalog/js/price-utils',
    'mage/translate',
    'Getnet_PaymentMagento/js/view/payment/lib/jquery/jquery.mask'
// eslint-disable-next-line no-unused-vars
], function (_, $, Component, mask, walletData, quote, priceUtils, $t, _mask) {
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
        totals: quote.getTotals(),

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
            console.log(self.hasWalletDataFormCredit());
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
         * Get instalments values
         * @returns {Object}
         */
        getInstalmentsValues() {
            var grandTotal,
                info_interest,
                min_installment,
                max_installment,
                installmentsCalcValues = {},
                max_div,
                limit,
                idx,
                interest,
                installment,
                totalInstallment,
                taxa;

            grandTotal = quote.totals().base_grand_total;

            info_interest = window.checkoutConfig.payment[this.getAuxiliaryCode()].info_interest;

            min_installment = window.checkoutConfig.payment[this.getAuxiliaryCode()].min_installment;

            max_installment = window.checkoutConfig.payment[this.getAuxiliaryCode()].max_installment;

            installmentsCalcValues = {};

            max_div = grandTotal / min_installment;

            max_div = parseInt(max_div, 10);

            if (max_div > max_installment) {
                max_div = max_installment;
            } else if (max_div > 12) {
                    max_div = 12;
                }

            limit = max_div;

            if (limit === 0) {
                limit = 1;
            }
            for (idx = 1; idx < info_interest.length; idx++) {
                if (idx > limit) {
                    break;
                }
                interest = info_interest[idx];
                if (interest > 0) {
                    taxa = interest / 100;
                    installment = (grandTotal * taxa + grandTotal) / idx;
                    totalInstallment = installment * idx;
                    if (installment > 5 && installment > min_installment) {
                        installmentsCalcValues[idx] = {
                            'installment': priceUtils.formatPrice(installment, quote.getPriceFormat()),
                            'totalInstallment': priceUtils.formatPrice(totalInstallment, quote.getPriceFormat()),
                            // eslint-disable-next-line max-len
                            'totalInterest': priceUtils.formatPrice(totalInstallment - grandTotal, quote.getPriceFormat()),
                            'interest': interest
                        };
                    }
                // eslint-disable-next-line eqeqeq
                } else if (interest == 0) {
                    if (grandTotal > 0) {
                        installmentsCalcValues[idx] = {
                            'installment': priceUtils.formatPrice(grandTotal / idx, quote.getPriceFormat()),
                            'totalInstallment': priceUtils.formatPrice(grandTotal, quote.getPriceFormat()),
                            'totalInterest': 0,
                            'interest': 0
                        };
                    }
                }
            }
            return installmentsCalcValues;
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
         * Get instalments
         * @returns {Array}
         */
        getInstallments() {
            var temp,
                inst,
                newArray = [],
                idx;

            temp = _.map(this.getInstalmentsValues(), function (value, key) {
                if (value['interest'] === 0) {
                    inst = $t('%1x of %2 not interest').replace('%1', key).replace('%2', value['installment']);
                } else if (value['interest'] < 0) {
                    // eslint-disable-next-line max-len
                    inst = $t('%1% of discount cash with total of %2').replace('%1', value['discount']).replace('%2', value['totalWithTheDiscount']);
                } else {
                    // eslint-disable-next-line max-len
                    inst = $t('%1x of %2 in the total value of %3').replace('%1', key).replace('%2', value['installment']).replace('%3', value['totalInstallment']);
                }

                return {
                    'value': key,
                    'installments': inst
                };
            });

            for (idx = 0; idx < temp.length; idx++) {
                if (temp[idx].installments !== 'undefined' && temp[idx].installments !== undefined) {
                    newArray.push(temp[idx]);
                }
            }

            return newArray;
        }
    });
});
