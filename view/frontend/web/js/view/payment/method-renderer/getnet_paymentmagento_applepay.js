/**
 * Copyright © Getnet. All rights reserved.
 *
 * @author    Bruno Elisei <brunoelisei@o2ti.com>
 * See LICENSE for license details.
 */
define([
    'jquery',
    'ko',
    'mage/translate',
    'Magento_Checkout/js/view/payment/default',
    'Magento_Checkout/js/action/redirect-on-success',
    'Magento_Checkout/js/model/totals',
    'Magento_Checkout/js/model/payment/additional-validators',
    'Magento_Ui/js/model/messageList',
    'Getnet_PaymentMagento/js/view/payment/gateway/calculate-installment',
    'Getnet_PaymentMagento/js/action/checkout/set-interest'
], function (
    $,
    ko,
    $t,
    Component,
    redirectOnSuccessAction,
    totals,
    additionalValidators,
    messageList,
    getnetInstallment,
    getnetSetInterest
) {
    'use strict';

    var SESSION_VERSION = 3;

    return Component.extend({
        defaults: {
            template: 'Getnet_PaymentMagento/payment/applepay',
            fingerPrint: 'Getnet_PaymentMagento/payment/fingerPrint',
            applePayToken: '',
            applePayCardType: '',
            applePayCardNumber: '',
            applePayInstallment: '',
            applePayReady: false,
            applePayChecked: false,
            totalsUpdating: false
        },

        /**
         * Initializes model instance.
         *
         * @returns {Object}
         */
        initObservable() {
            this._super().observe([
                'applePayInstallment',
                'applePayReady',
                'applePayChecked',
                'totalsUpdating'
            ]);

            this.canPay = ko.pureComputed(function () {
                return this.applePayReady() &&
                    !!this.applePayInstallment() &&
                    !this.totalsUpdating();
            }, this);

            return this;
        },

        /**
         * Init component
         */
        initialize() {
            var self = this;

            this._super();

            // The interest follows the selected installment; it is cleared when the method is deselected.
            this.applePayInstallment.subscribe(function (value) {
                // A token issued for another total must never be reused.
                self.applePayToken = '';
                self.updateInterest(value ? value : 0);
            });

            this.isChecked.subscribe(function (code) {
                if (code === self.getCode()) {
                    self.updateInterest(self.applePayInstallment() ? self.applePayInstallment() : 0);
                } else if (self.applePayInstallment()) {
                    self.updateInterest(0);
                }
            });

            this.applePayReady(this.isAvailable());
            this.applePayChecked(true);
        },

        /**
         * Get code
         * @returns {String}
         */
        getCode() {
            return 'getnet_paymentmagento_applepay';
        },

        /**
         * Is the method selected (the base payment component does not provide it, unlike cc-form)
         * @returns {Boolean}
         */
        isActive() {
            return this.getCode() === this.isChecked();
        },

        /**
         * Config of the method.
         * @returns {Object}
         */
        getMethodConfig() {
            return window.checkoutConfig.payment[this.getCode()];
        },

        /**
         * Get title
         * @returns {String}
         */
        getTitle() {
            return this.getMethodConfig().title;
        },

        /**
         * Get data
         * @returns {Object}
         */
        getData() {
            return {
                'method': this.getCode(),
                'additional_data': {
                    'applepay_token': this.applePayToken,
                    'cc_installments': this.applePayInstallment(),
                    'cc_type': this.applePayCardType,
                    'cc_number': this.applePayCardNumber
                }
            };
        },

        /**
         * Installment options (same rules and interest as the credit card method).
         * @returns {Array}
         */
        getCalculeteInstallments() {
            return getnetInstallment.getInstallments();
        },

        /**
         * Update the interest in the totals and lock the button until the grand total is final.
         *
         * @param {Number|String} installment
         * @returns {void}
         */
        updateInterest(installment) {
            var self = this;

            this.totalsUpdating(true);
            $.when(getnetSetInterest.getnetInterest(installment)).always(function () {
                self.totalsUpdating(false);
            });
        },

        /**
         * Apple Pay is only available in Safari/Apple devices (ApplePaySession).
         * @returns {Boolean}
         */
        isAvailable() {
            try {
                return !!(window.ApplePaySession &&
                    window.ApplePaySession.canMakePayments() &&
                    window.ApplePaySession.supportsVersion(SESSION_VERSION));
            } catch (e) {
                return false;
            }
        },

        /**
         * Inline style of the native Apple Pay button.
         * @returns {String}
         */
        getButtonStyle() {
            var config = this.getMethodConfig();

            return '-webkit-appearance: -apple-pay-button; ' +
                '-apple-pay-button-type: ' + config.buttonType + '; ' +
                '-apple-pay-button-style: ' + config.buttonStyle + '; ' +
                'width: 100%; height: 44px; border: 0; cursor: pointer;';
        },

        /**
         * Grand total (already with the interest of the selected installment).
         * @returns {String}
         */
        getTotalPrice() {
            var segment = totals.getSegment('grand_total');

            return parseFloat(segment ? segment.value : 0).toFixed(2);
        },

        /**
         * Show the error in the checkout.
         *
         * @param {String} message
         * @returns {void}
         */
        showError(message) {
            messageList.addErrorMessage({
                message: message
            });
        },

        /**
         * Button click: validate, open the Apple Pay sheet and place the order with the token.
         *
         * The session must be created synchronously in the click (user gesture required by Apple).
         *
         * @returns {void}
         */
        onApplePayClick() {
            var self = this,
                config = this.getMethodConfig(),
                total = {
                    label: config.merchantName,
                    type: 'final',
                    amount: this.getTotalPrice()
                },
                session;

            if (!this.canPay() || !additionalValidators.validate()) {
                return;
            }

            session = new window.ApplePaySession(SESSION_VERSION, {
                countryCode: config.countryCode,
                currencyCode: config.currencyCode,
                supportedNetworks: config.supportedNetworks,
                merchantCapabilities: config.merchantCapabilities,
                total: total
            });

            session.onvalidatemerchant = function (event) {
                $.ajax({
                    url: config.validationUrl,
                    type: 'POST',
                    dataType: 'json',
                    data: {
                        validationURL: event.validationURL,
                        form_key: window.FORM_KEY
                    }
                }).done(function (merchantSession) {
                    session.completeMerchantValidation(merchantSession);
                }).fail(function () {
                    session.abort();
                    self.showError($t('Could not complete the payment with Apple Pay. Please try again or choose another payment method.'));
                });
            };

            session.onpaymentmethodselected = function () {
                session.completePaymentMethodSelection({
                    newTotal: total
                });
            };

            session.onpaymentauthorized = function (event) {
                self.authorize(session, event.payment.token);
            };

            session.begin();
        },

        /**
         * Place the order with the authorized Apple Pay token and close the sheet with the result.
         *
         * @param {Object} session
         * @param {Object} token
         * @returns {void}
         */
        authorize(session, token) {
            var self = this,
                method = token.paymentMethod || {};

            // Debit cards do not accept installments: fail before charging anything.
            if (method.type === 'debit' && parseInt(this.applePayInstallment(), 10) > 1) {
                session.completePayment(window.ApplePaySession.STATUS_FAILURE);
                this.showError($t('Installments are not available for this card. Debit cards of Apple Pay only allow payment in full: select 1x or choose another card.'));

                return;
            }

            // Raw token, exactly as received: the backend only Base64 encodes it.
            this.applePayToken = JSON.stringify(token);
            this.applePayCardType = method.network ? method.network : '';
            this.applePayCardNumber = method.displayName ? method.displayName : '';

            this.isPlaceOrderActionAllowed(false);
            this.getPlaceOrderDeferredObject().done(function () {
                session.completePayment(window.ApplePaySession.STATUS_SUCCESS);
                self.afterPlaceOrder();

                if (self.redirectAfterPlaceOrder) {
                    redirectOnSuccessAction.execute();
                }
            }).fail(function () {
                session.completePayment(window.ApplePaySession.STATUS_FAILURE);
            }).always(function () {
                self.isPlaceOrderActionAllowed(true);
            });

            // Single use token: never kept after the order data was built.
            this.applePayToken = '';
        },

        /**
         * Antifraud fingerprint is shared with the credit card method (same session).
         * @returns {Boolean}
         */
        FraudManager() {
            return window.checkoutConfig.payment.getnet_paymentmagento_cc.fraud_manager;
        },

        /**
         * FingerPrint Url
         * @returns {String}
         */
        fingerPrintUrl() {
            var cc = window.checkoutConfig.payment.getnet_paymentmagento_cc,
                host = cc.fingerPrintEnv === 'sandbox' ? 'api.sandbox.globalgetnet.com' : 'api.globalgetnet.com';

            return 'https://' + host + '/dpm/digital-platform/antifraud/afdf.js?session=' +
                cc.fingerPrintSessionId + '&country=BR';
        }
    });
});
