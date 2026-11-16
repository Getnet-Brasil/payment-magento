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
    totals,
    additionalValidators,
    messageList,
    getnetInstallment,
    getnetSetInterest
) {
    'use strict';

    var PAY_JS_URL = 'https://pay.google.com/gp/p/js/pay.js',
        payJsPromise = null;

    /**
     * Load the Google Pay JS library once.
     *
     * @returns {Promise}
     */
    function loadPayJs() {
        if (payJsPromise) {
            return payJsPromise;
        }

        payJsPromise = new Promise(function (resolve, reject) {
            var script;

            if (window.google && window.google.payments && window.google.payments.api) {
                resolve();

                return;
            }

            script = document.createElement('script');
            script.async = true;
            script.src = PAY_JS_URL;
            script.onload = resolve;
            script.onerror = function () {
                payJsPromise = null;
                reject(new Error('Google Pay library could not be loaded'));
            };
            document.head.appendChild(script);
        });

        return payJsPromise;
    }

    return Component.extend({
        defaults: {
            template: 'Getnet_PaymentMagento/payment/googlepay',
            fingerPrint: 'Getnet_PaymentMagento/payment/fingerPrint',
            googlePayToken: '',
            googlePayCardType: '',
            googlePayCardNumber: '',
            googlePayInstallment: '',
            googlePayReady: false,
            googlePayChecked: false,
            totalsUpdating: false
        },

        paymentsClient: null,

        /**
         * Initializes model instance.
         *
         * @returns {Object}
         */
        initObservable() {
            this._super().observe([
                'googlePayInstallment',
                'googlePayReady',
                'googlePayChecked',
                'totalsUpdating'
            ]);

            this.canPay = ko.pureComputed(function () {
                return this.googlePayReady() &&
                    !!this.googlePayInstallment() &&
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
            this.googlePayInstallment.subscribe(function (value) {
                // A token issued for another total must never be reused.
                self.googlePayToken = '';
                self.updateInterest(value ? value : 0);
            });

            // Coupon or shipping changes after pricing: re-price, otherwise place order is refused.
            totals.totals.subscribe(function () {
                if (self.isActive() && getnetSetInterest.needsReprice(self.googlePayInstallment())) {
                    self.googlePayToken = '';
                    self.updateInterest(self.googlePayInstallment());
                }
            });

            this.isChecked.subscribe(function (code) {
                if (code === self.getCode()) {
                    self.updateInterest(self.googlePayInstallment() ? self.googlePayInstallment() : 0);
                } else if (self.googlePayInstallment()) {
                    self.updateInterest(0);
                }
            });

            loadPayJs().then(function () {
                return self.checkReadyToPay();
            }).catch(function () {
                self.googlePayReady(false);
                self.googlePayChecked(true);
            });
        },

        /**
         * Get code
         * @returns {String}
         */
        getCode() {
            return 'getnet_paymentmagento_googlepay';
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
                    'googlepay_token': this.googlePayToken,
                    'cc_installments': this.googlePayInstallment(),
                    'cc_type': this.googlePayCardType,
                    'cc_number': this.googlePayCardNumber
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
         * Google Pay payments client.
         * @returns {Object}
         */
        getPaymentsClient() {
            var config = this.getMethodConfig();

            if (!this.paymentsClient) {
                this.paymentsClient = new window.google.payments.api.PaymentsClient({
                    environment: config.environment
                });
            }

            return this.paymentsClient;
        },

        /**
         * Base card payment method (without the tokenization).
         * @returns {Object}
         */
        getBaseCardMethod() {
            var config = this.getMethodConfig();

            return {
                type: 'CARD',
                parameters: {
                    allowedAuthMethods: config.allowedAuthMethods,
                    allowedCardNetworks: config.allowedCardNetworks
                }
            };
        },

        /**
         * Card payment method with the Getnet tokenization specification.
         * @returns {Object}
         */
        getTokenizedCardMethod() {
            var config = this.getMethodConfig(),
                method = $.extend(true, {}, this.getBaseCardMethod(), {
                    tokenizationSpecification: {
                        type: 'PAYMENT_GATEWAY',
                        parameters: {
                            gateway: config.gateway,
                            gatewayMerchantId: config.gatewayMerchantId
                        }
                    }
                });

            return method;
        },

        /**
         * Hide the method when the customer has no usable Google Pay card.
         * @returns {Promise}
         */
        checkReadyToPay() {
            var self = this;

            return this.getPaymentsClient().isReadyToPay({
                apiVersion: 2,
                apiVersionMinor: 0,
                allowedPaymentMethods: [this.getBaseCardMethod()]
            }).then(function (response) {
                self.googlePayReady(!!response.result);
                self.googlePayChecked(true);
            });
        },

        /**
         * Render the official Google Pay button.
         *
         * @param {HTMLElement} element
         * @returns {void}
         */
        renderButton(element) {
            var self = this,
                config = this.getMethodConfig(),
                render = function () {
                    var button;

                    if (!self.googlePayReady() || $(element).children().length) {
                        return;
                    }

                    button = self.getPaymentsClient().createButton({
                        buttonColor: config.buttonColor,
                        buttonType: config.buttonType,
                        buttonSizeMode: 'fill',
                        buttonLocale: 'pt',
                        onClick: function () {
                            self.onGooglePayClick();
                        }
                    });
                    element.appendChild(button);
                };

            if (this.googlePayReady()) {
                render();
            } else {
                this.googlePayReady.subscribe(render);
            }
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
         * Button click: validate, open the Google Pay sheet and place the order with the token.
         *
         * @returns {void}
         */
        onGooglePayClick() {
            var self = this,
                config = this.getMethodConfig(),
                request;

            if (!this.canPay() || !additionalValidators.validate()) {
                return;
            }

            request = {
                apiVersion: 2,
                apiVersionMinor: 0,
                allowedPaymentMethods: [this.getTokenizedCardMethod()],
                merchantInfo: {
                    merchantName: config.merchantName
                },
                transactionInfo: {
                    countryCode: config.countryCode,
                    currencyCode: config.currencyCode,
                    totalPriceStatus: 'FINAL',
                    totalPrice: this.getTotalPrice()
                }
            };

            if (config.googleMerchantId) {
                request.merchantInfo.merchantId = config.googleMerchantId;
            }

            this.getPaymentsClient().loadPaymentData(request).then(function (paymentData) {
                var method = paymentData.paymentMethodData;

                // Raw string, exactly as received: the backend only Base64 encodes it.
                self.googlePayToken = method.tokenizationData.token;
                self.googlePayCardType = method.info ? method.info.cardNetwork : '';
                self.googlePayCardNumber = method.info ? method.info.cardDetails : '';

                self.placeOrder();
                // Single use token: never kept after the order data was built.
                self.googlePayToken = '';
            }).catch(function (error) {
                if (error && error.statusCode === 'CANCELED') {
                    return;
                }
                messageList.addErrorMessage({
                    message: $t('Could not complete the payment with Google Pay. Please try again or choose another payment method.')
                });
            });
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
