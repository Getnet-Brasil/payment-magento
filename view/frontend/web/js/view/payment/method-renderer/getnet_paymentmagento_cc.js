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
    'Magento_Vault/js/view/payment/vault-enabler',
    'Magento_Checkout/js/model/full-screen-loader',
    'Magento_Payment/js/model/credit-card-validation/credit-card-data',
    'Getnet_PaymentMagento/js/view/payment/gateway/custom-validation',
    'Getnet_PaymentMagento/js/view/payment/lib/jquery/jquery.mask',
    'Getnet_PaymentMagento/js/view/payment/gateway/calculate-installment',
    'Magento_Checkout/js/model/quote',
    'ko',
    'Magento_Checkout/js/model/url-builder',
    'mage/url',
    'Magento_Customer/js/model/customer',
    'Magento_Payment/js/model/credit-card-validation/validator'
], function (_,
        $,
        Component,
        VaultEnabler,
        fullScreenLoader,
        creditCardData,
        _customValidation,
        _mask,
        getnetInstallment,
        quote,
        _ko,
        urlBuilder,
        urlFormatter,
        customer
    ) {
    'use strict';

    return Component.extend({
        defaults: {
            active: false,
            template: 'Getnet_PaymentMagento/payment/cc',
            ccForm: 'Getnet_PaymentMagento/payment/cc-form',
            fingerPrint: 'Getnet_PaymentMagento/payment/fingerPrint',
            creditCardNumberToken: '',
            creditCardHolderTaxDocument: '',
            creditCardHolderPhone: '',
            creditCardInstallment: '',
            creditCardholderName: '',
            creditCardNumber: '',
            creditCardVerificationNumber: '',
            creditCardExpMonth: '',
            creditCardExpYear: '',
            creditCardType: '',
            selectedCardType: '',
            creditCardPublicId: ''
        },

        /**
         * Initializes model instance.
         *
         * @returns {Object}
         */
        initObservable() {
            this._super().observe([
                'active',
                'creditCardInstallment',
                'creditCardNumberToken',
                'creditCardholderName',
                'creditCardHolderTaxDocument',
                'creditCardHolderPhone',
                'creditCardPublicId'
            ]);
            return this;
        },

        /**
         * Get code
         * @returns {String}
         */
        getCode() {
            return 'getnet_paymentmagento_cc';
        },

        /**
         * Init component
         */
        initialize() {
            var self = this,
                vat,
                tel,
                typeMaskVat;

            this._super();

            this.vaultEnabler = new VaultEnabler();
            this.vaultEnabler.setPaymentCode(this.getVaultCode());

            vat = $('#getnet_paymentmagento_cc_tax_document'),

            tel = $('#getnet_paymentmagento_cc_holder_phone');

            tel.mask('(00)00000-0000', { clearIfNotMatch: true });

            self.creditCardInstallment.subscribe(function (value) {
                creditCardData.creditCardInstallment = value;
            });

            self.creditCardNumberToken.subscribe(function (value) {
                creditCardData.creditCardNumberToken = value;
            });

            self.creditCardholderName.subscribe(function (value) {
                creditCardData.creditCardholderName = value;
            });

            self.creditCardHolderTaxDocument.subscribe(function (value) {
                typeMaskVat = value.replace(/\D/g, '').length <= 11 ? '000.000.000-009' : '00.000.000/0000-00';

                vat.mask(typeMaskVat, { clearIfNotMatch: true });
                creditCardData.creditCardHolderTaxDocument = value;
            });

            self.creditCardHolderPhone.subscribe(function (value) {
                creditCardData.creditCardHolderPhone = value;
            });

            self.selectedCardType.subscribe(function (value) {
                $('#getnet_paymentmagento_cc_number').unmask();
                if (value === 'VI' || value === 'MC' || value === 'ELO' || value === 'HC' || value === 'HI') {
                    $('#getnet_paymentmagento_cc_number').mask('0000 0000 0000 0000');
                }
                if (value === 'DN') {
                    $('#getnet_paymentmagento_cc_number').mask('0000 000000 0000');
                }
                if (value === 'AE') {
                    $('#getnet_paymentmagento_cc_number').mask('0000 000000 00000');
                }
                creditCardData.selectedCardType = value;
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
            this.getnetTokenizeCard();
        },

        /**
         * Get Tokenize
         * @returns {void}
         */
        getnetTokenizeCard() {
            var self = this,
                cardNumber = this.creditCardNumber().replace(/\D/g, ''),
                serviceUrl,
                payload,
                isUsed = this.vaultEnabler.isVaultEnabled(),
                saveCard = this.vaultEnabler.isActivePaymentTokenEnabler(),
                quoteId = quote.getQuoteId(),
                cardId,
                token;

            fullScreenLoader.startLoader();

            serviceUrl = urlBuilder.createUrl('/carts/mine/generate-credit-card-number-token', {});
            payload = {
                cartId: quoteId,
                cardNumber: {
                    card_number: cardNumber
                }
            };

            if (saveCard && isUsed) {
                serviceUrl = urlBuilder.createUrl('/carts/mine/create-vault', {});
                payload = {
                    cartId: quoteId,
                    vaultData: {
                        card_number: cardNumber,
                        customer_email: window.checkoutConfig.customerData.email,
                        expiration_month: this.creditCardExpMonth(),
                        expiration_year: this.creditCardExpYear().substr(-2),
                        cardholder_name: this.creditCardholderName()
                    }
                };
            }
            if (!customer.isLoggedIn()) {
                serviceUrl = urlBuilder.createUrl('/guest-carts/:cartId/generate-credit-card-number-token', {
                    cartId: quoteId
                });
                payload = {
                    cartId: quoteId,
                    cardNumber: {
                        card_number: cardNumber
                    }
                };
            }

            $.ajax({
                url: urlFormatter.build(serviceUrl),
                data: JSON.stringify(payload),
                global: true,
                contentType: 'application/json',
                type: 'POST',
                async: true
            }).done(
                function (response) {

                    if (response[0].success) {
                        token = response[0].number_token;
                        self.creditCardNumberToken(token);
                        if (saveCard) {
                            cardId = response[0].card_id;
                            self.creditCardPublicId(cardId);
                        }
                        self.placeOrder();
                    }

                    if (!response[0].success) {
                        self.messageContainer.addErrorMessage({'message': response[0].message.text});
                    }
                }
            ).always(
                function () {
                    fullScreenLoader.stopLoader();
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
                    'cc_number_token': this.creditCardNumberToken(),
                    'cc_cardholder_name': this.creditCardholderName(),
                    'cc_number': this.creditCardNumber().substr(-4),
                    'cc_type': this.creditCardType(),
                    'cc_cid': this.creditCardVerificationNumber(),
                    'cc_exp_month': this.creditCardExpMonth(),
                    'cc_exp_year': this.creditCardExpYear(),
                    'cc_installments': this.creditCardInstallment(),
                    'cc_holder_tax_document': this.creditCardHolderTaxDocument(),
                    'cc_holder_phone': this.creditCardHolderPhone(),
                    'cc_public_id': this.creditCardPublicId()
                }
            };

            data['additional_data'] = _.extend(data['additional_data'], this.additionalData);
            this.vaultEnabler.visitAdditionalData(data);
            return data;
        },

        /**
         * Has verification
         * @returns {boolean}
         */
        hasVerification() {
            return window.checkoutConfig.payment[this.getCode()].useCvv;
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
         * Is show legend
         * @returns {Boolean}
         */
        isShowLegend() {
            return true;
        },

        /**
         * Tax document capture
         * @returns {Boolean}
         */
        TaxDocumentCapture() {
            return window.checkoutConfig.payment[this.getCode()].tax_document_capture;
        },

        /**
         * Phone capture
         * @returns {Boolean}
         */
        PhoneCapture() {
            return window.checkoutConfig.payment[this.getCode()].phone_capture;
        },

        /**
         * Get Calculete instalments
         * @returns {Array}
         */
        getCalculeteInstallments() {
            return getnetInstallment.getInstallments();
        },

        /**
         * Fraud Manager
         * @returns {Boolean}
         */
        FraudManager() {
            return window.checkoutConfig.payment[this.getCode()].fraud_manager;
        },

        /**
         * Tax document capture
         * @returns {Boolean}
         */
        getVaultCode() {
            return window.checkoutConfig.payment[this.getCode()].ccVaultCode;
        },

        /**
         * Is vault enabled
         * @returns {Boolean}
         */
        isVaultEnabled: function () {
            return this.vaultEnabler.isVaultEnabled();
        },

        /**
         * FingerPrint session id
         * @returns {String}
         */
        fingerPrintSessionId() {
            return window.checkoutConfig.payment[this.getCode()].fingerPrintSessionId;
        },

        /**
         * FingerPrint code
         * @returns {String}
         */
        fingerPrintCode() {
            return window.checkoutConfig.payment[this.getCode()].fingerPrintCode;
        },

        /**
         * FingerPrint Url
         * @returns {String}
         */
        fingerPrintUrl() {
            var url = 'https://h.online-metrix.net/fp/tags?org_id=%1&session_id=%2',
                code = this.fingerPrintCode(),
                sessionId = this.fingerPrintSessionId();

            return url.replace('%1', code).replace('%2', sessionId);
        }
    });
});
