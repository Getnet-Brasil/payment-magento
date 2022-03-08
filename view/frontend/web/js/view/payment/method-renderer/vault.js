/**
 * Copyright © Getnet. All rights reserved.
 *
 * @author    Bruno Elisei <brunoelisei@o2ti.com>
 * See LICENSE for license details.
 */
define([
    'underscore',
    'jquery',
    'ko',
    'Magento_Vault/js/view/payment/method-renderer/vault',
    'Magento_Payment/js/model/credit-card-validation/credit-card-data',
    'Magento_Checkout/js/model/quote',
    'Magento_Checkout/js/model/url-builder',
    'mage/url',
    'Getnet_PaymentMagento/js/view/payment/gateway/calculate-installment'
], function (
    _,
    $,
    _ko,
    VaultComponent,
    creditCardData,
    quote,
    urlBuilder,
    urlFormatter,
    getnetInstallment
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

                serviceUrl = urlBuilder.createUrl('/carts/mine/get-details-card-id', {});
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
         * Get Calculete instalments
         * @returns {Array}
         */
        getCalculeteInstallments() {
            return getnetInstallment.getInstallments();
        }
    });
});
