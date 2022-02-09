/**
 * Copyright © Getnet. All rights reserved.
 *
 * @author    Bruno Elisei <brunoelisei@o2ti.com>
 * See LICENSE for license details.
 */
define([
    'underscore',
    'jquery',
    'Magento_Vault/js/view/payment/method-renderer/vault',
    'Magento_Payment/js/model/credit-card-validation/credit-card-data',
    'Magento_Checkout/js/model/quote',
    'Magento_Catalog/js/price-utils',
    'mage/translate',
    'Magento_Checkout/js/model/url-builder',
    'mage/url',
    'ko'
], function (
    _,
    $,
    VaultComponent,
    creditCardData,
    quote,
    priceUtils,
    $t,
    urlBuilder,
    urlFormatter,
    // eslint-disable-next-line no-unused-vars
    ko
    ) {
    'use strict';

    return VaultComponent.extend({
        defaults: {
            active: false,
            template: 'Getnet_PaymentMagento/payment/vault',
            vaultForm: 'Getnet_PaymentMagento/payment/vault-form',
            creditCardVerificationNumber: '',
            creditCardInstallment: '',
            creditCardNumberToken: '',
            creditCardholderName: '',
            creditCardNumber: '',
            creditCardExpMonth: '',
            creditCardExpYear: '',
            creditCardType: ''
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
                'creditCardVerificationNumber',
                'creditCardInstallment',
                'creditCardNumberToken',
                'creditCardholderName',
                'creditCardNumber',
                'creditCardExpMonth',
                'creditCardExpYear',
                'creditCardType'
            ]);
            return this;
        },

        /**
         * Get auxiliary code
         * @returns {String}
         */
        getAuxiliaryCode() {
            return 'getnet_paymentmagento_cc';
        },

        /**
         * Get code
         * @returns {String}
         */
        getCode() {
            return 'getnet_paymentmagento_cc_vault';
        },

        /**
         * Init component
         */
        initialize() {
            var self = this;

            this._super();

            self.creditCardInstallment.subscribe(function (value) {
                creditCardData.creditCardInstallment = value;
            });

            self.creditCardVerificationNumber.subscribe(function (value) {
                creditCardData.creditCardVerificationNumber = value;
            });

            self.creditCardNumberToken.subscribe(function (value) {
                creditCardData.creditCardNumberToken = value;
            });

            self.creditCardholderName.subscribe(function (value) {
                creditCardData.creditCardholderName = value;
            });

            self.creditCardNumber.subscribe(function (value) {
                creditCardData.creditCardNumber = value;
            });

            self.creditCardExpMonth.subscribe(function (value) {
                creditCardData.creditCardExpMonth = value;
            });

            self.creditCardExpYear.subscribe(function (value) {
                creditCardData.creditCardExpYear = value;
            });

            self.creditCardType.subscribe(function (value) {
                creditCardData.creditCardType = value;
            });
        },

        /**
         * Is Active
         * @returns {Boolean}
         */
        isActive() {
            var active = this.getId() === this.isChecked();

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
            this.getCardIdDetails();
            this.placeOrder();
        },

        /**
         * Get card id details
         * @returns {void}
         */
        getCardIdDetails() {
            var self = this,
                cardId = this.getToken(),
                serviceUrl,
                payload,
                quoteId = quote.getQuoteId(),
                cardDetails;

                cardId = '514886d5-6063-4ab4-9a45-1468f0559634';
                serviceUrl = urlBuilder.createUrl('/carts/mine/get-detatails-card-id', {});
                payload = {
                    cartId: quoteId,
                    cardId: {
                        card_id: cardId
                    }
                };
                $.ajax({
                    url: urlFormatter.build(serviceUrl),
                    data: JSON.stringify(payload),
                    global: false,
                    contentType: 'application/json',
                    type: 'POST',
                    async: false
                }).done(
                    function (response) {
                        if (response[0].success) {
                            cardDetails = response[0].card;
                            self.creditCardNumberToken(cardDetails.number_token);
                            self.creditCardNumber(cardDetails.last_four_digits);
                            self.creditCardholderName(cardDetails.cardholder_name);
                            self.creditCardExpMonth(cardDetails.expiration_month);
                            self.creditCardExpYear(cardDetails.expiration_year);
                            self.creditCardType(cardDetails.brand);
                        }
                    }
                );
        },

        /**
         * Get data
         * @returns {Object}
         */
        getData() {
            var data = {
                'method': this.getCode(),
                'additional_data': {
                    'cc_cid': this.creditCardVerificationNumber(),
                    'cc_installments': this.creditCardInstallment(),
                    'cc_number_token': this.creditCardNumberToken(),
                    'cc_cardholder_name': this.creditCardholderName(),
                    'cc_number': this.creditCardNumber(),
                    'cc_exp_month': this.creditCardExpMonth(),
                    'cc_exp_year': this.creditCardExpYear(),
                    'public_hash': this.getToken()
                }

            };

            return data;
        },

        /**
         * Is show legend
         * @returns {boolean}
         */
        isShowLegend() {
            return true;
        },

        /**
         * Get Token
         * @returns {string}
         */
        getToken() {
            return this.publicHash;
        },

        /**
         * Get masked card
         * @returns {string}
         */
        getMaskedCard() {
            return this.details['cc_last4'];
        },

        /**
         * Get expiration date
         * @returns {string}
         */
        getExpirationDate() {
            return this.details['cc_exp_month'] + '/' + this.details['cc_exp_year'];
        },

        /**
         * Get card type
         * @returns {string}
         */
        getCardType() {
            return this.details['cc_type'];
        },

        /**
         * Has verification
         * @returns {boolean}
         */
        hasVerification() {
            return window.checkoutConfig.payment[this.getCode()].useCvv;
        },

        /**
         * Get payment icons
         * @param {String} type
         * @returns {Boolean}
         */
        getIcons(type) {
            return window.checkoutConfig.payment[this.getCode()].icons.hasOwnProperty(type) ?
                window.checkoutConfig.payment[this.getCode()].icons[type]
                : false;
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
                pw,
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
