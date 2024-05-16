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
    'Magento_Checkout/js/model/full-screen-loader',
    'Getnet_PaymentMagento/js/view/payment/gateway/custom-validation',
    'Getnet_PaymentMagento/js/view/payment/lib/jquery/jquery.mask',
    'Getnet_PaymentMagento/js/view/payment/gateway/calculate-installment',
    'Getnet_PaymentMagento/js/action/checkout/set-two-interest',
    'Magento_Checkout/js/model/quote',
    'ko',
    'Magento_Checkout/js/model/url-builder',
    'mage/url',
    'Magento_Customer/js/model/customer',
    'Magento_Payment/js/model/credit-card-validation/credit-card-number-validator',
    'Magento_Catalog/js/price-utils',
    'Magento_Payment/js/model/credit-card-validation/validator'
], function (_,
        $,
        Component,
        creditCardData,
        fullScreenLoader,
        _customValidation,
        _mask,
        getnetInstallment,
        getnetSetInterest,
        quote,
        _ko,
        urlBuilder,
        urlFormatter,
        customer,
        cardNumberValidator,
        priceUtils
    ) {
    'use strict';

    return Component.extend({
        totals: quote.getTotals(),
        defaults: {
            active: false,
            template: 'Getnet_PaymentMagento/payment/cc',
            ccForm: 'Getnet_PaymentMagento/payment/two-cc-form',
            fingerPrint: 'Getnet_PaymentMagento/payment/fingerPrint',
            creditCardNumberToken: '',
            creditCardHolderTaxDocument: '',
            creditCardHolderPhone: '',
            creditCardInstallment: '',
            creditCardHolderName: '',
            creditCardNumber: '',
            creditCardVerificationNumber: '',
            creditCardExpMonth: '',
            creditCardExpYear: '',
            creditCardType: '',
            selectedCardType: '',
            firstPaymentAmount: (quote.totals().base_grand_total / 2).toFixed(2),
            secondaryPaymentAmount: quote.totals().base_grand_total - (quote.totals().base_grand_total / 2).toFixed(2),
            minScale: 5,
            maxScale: quote.totals().base_grand_total - 5,
            creditCardSecondaryNumberToken: '',
            creditCardSecondaryInstallment: '',
            creditCardSecondaryHolderName: '',
            creditCardSecondaryNumber: '',
            creditCardSecondaryVerificationNumber: '',
            creditCardSecondaryExpMonth: '',
            creditCardSecondaryExpYear: '',
            creditCardSecondaryType: '',
            selectedCardSecondaryType: ''
        },

        /**
         * Initializes model instance.
         *
         * @returns {Object}
         */
        initObservable() {
            this._super()
                .observe([
                    'active',
                    'creditCardInstallment',
                    'creditCardNumberToken',
                    'creditCardHolderName',
                    'creditCardHolderTaxDocument',
                    'creditCardHolderPhone',
                    'firstPaymentAmount',
                    'secondaryPaymentAmount',
                    'creditCardSecondaryNumberToken',
                    'creditCardSecondaryInstallment',
                    'creditCardSecondaryHolderName',
                    'creditCardSecondaryNumber',
                    'creditCardSecondaryVerificationNumber',
                    'creditCardSecondaryExpMonth',
                    'creditCardSecondaryExpYear',
                    'creditCardSecondaryType',
                    'selectedCardSecondaryType'
                ]);
            return this;
        },

        /**
         * Get code
         * @returns {String}
         */
        getCode() {
            return 'getnet_paymentmagento_two_cc';
        },

        /**
         * Init component
         */
        initialize() {
            var self = this,
                vat = $('#getnet_paymentmagento_two_cc_tax_document'),
                tel = $('#getnet_paymentmagento_two_cc_holder_phone'),
                currentInterest = quote.totals()['total_segments'].getnet_interest_amount,
                baseGrandTotal = quote.totals().base_grand_total,
                typeMaskVat;

            this._super();

            tel.mask('(00)00000-0000', { clearIfNotMatch: true });

            self.active.subscribe(() => {
                self.creditCardInstallment(null);
                getnetSetInterest.getnetInterest(0);
            });

            self.creditCardHolderTaxDocument.subscribe(function (value) {
                typeMaskVat = value.replace(/\D/g, '').length <= 11 ? '000.000.000-009' : '00.000.000/0000-00';

                vat.mask(typeMaskVat, { clearIfNotMatch: true });
                creditCardData.creditCardHolderTaxDocument = value;
            });

            self.creditCardHolderPhone.subscribe(function (value) {
                creditCardData.creditCardHolderPhone = value;
            });

            self.creditCardInstallment.subscribe(function (value) {
                self.addInterest(0);
                self.creditCardSecondaryInstallment(null);
                creditCardData.creditCardInstallment = value;
            });

            if (!self.creditCardInstallment()) {
                self.addInterest(0);
                self.addInterest(1);
            }

            self.creditCardNumberToken.subscribe(function (value) {
                creditCardData.creditCardNumberToken = value;
            });

            self.creditCardHolderName.subscribe(function (value) {
                creditCardData.creditCardHolderName = value;
            });

            self.selectedCardType.subscribe(function (value) {
                $('#getnet_paymentmagento_two_cc_number').unmask();
                $('#getnet_paymentmagento_two_cc_number').mask('0000 0000 0000 0000 ####');
                if (value === 'DN') {
                    $('#getnet_paymentmagento_two_cc_number').unmask();
                    $('#getnet_paymentmagento_two_cc_number').mask('0000 000000 0000');
                }
                if (value === 'AE') {
                    $('#getnet_paymentmagento_two_cc_number').unmask();
                    $('#getnet_paymentmagento_two_cc_number').mask('0000 000000 00000');
                }
                creditCardData.selectedCardType = value;
            });

            self.firstPaymentAmount.subscribe(function (value) {
                self.addInterest(0);
                self.addInterest(1);
                $('#getnet_paymentmagento_two_cc_first_payment_amount').mask('#0.00', {reverse: true});
                self.secondaryPaymentAmount(self.minScale);
                creditCardData.firstPaymentAmount = baseGrandTotal - 5  - currentInterest;

                if (value >= self.minScale && value <= self.maxScale) {
                    creditCardData.firstPaymentAmount = value;
                    self.secondaryPaymentAmount(baseGrandTotal - value);
                    creditCardData.secondaryPaymentAmount = baseGrandTotal - value - currentInterest;
                }
            });

            self.secondaryPaymentAmount.subscribe(function (value) {
                creditCardData.secondaryPaymentAmount = value;
            });

            self.subscribeDataTwoCard();
        },

        /**
         * Subscrive Data two Card
         * @returns {void}
         */
        subscribeDataTwoCard() {
            var self = this;

            self.creditCardSecondaryNumber.subscribe(function (value) {
                var result;

                self.selectedCardSecondaryType(null);

                if (value === '' || value === null) {
                    return false;
                }
                result = cardNumberValidator(value);

                if (!result.isPotentiallyValid && !result.isValid) {
                    return false;
                }

                if (result.card !== null) {
                    self.selectedCardSecondaryType(result.card.type);
                    creditCardData.creditCard = result.card;
                }

                if (result.isValid) {
                    creditCardData.creditCardSecondaryNumber = value;
                    self.creditCardSecondaryType(result.card.type);
                }
            });

            self.creditCardSecondaryExpYear.subscribe(function (value) {
                creditCardData.secondaryExpirationYear = value;
            });

            self.creditCardExpMonth.subscribe(function (value) {
                creditCardData.secondaryExpirationMonth = value;
            });

            self.creditCardSecondaryVerificationNumber.subscribe(function (value) {
                creditCardData.secondaryCvvCode = value;
            });

            self.creditCardSecondaryInstallment.subscribe(function (value) {
                self.addInterest(1);
                creditCardData.creditCardSecondaryInstallment = value;
            });

            self.creditCardSecondaryNumberToken.subscribe(function (value) {
                creditCardData.creditCardSecondaryNumberToken = value;
            });

            self.creditCardSecondaryHolderName.subscribe(function (value) {
                creditCardData.creditSecondaryCardHolderName = value;
            });

            self.creditCardSecondaryType.subscribe(function (value) {
                creditCardData.creditCardSecondaryType = value;
                creditCardData.selectedCardSecondaryType = value;
            });
        },

        /**
         * Add Interest in totals
         * @param {Integer} idx
         * @returns {void}
         */
        addInterest(idx) {
            var self = this,
                amount = parseFloat(self.firstPaymentAmount()).toFixed(2),
                selectInstallment = self.creditCardInstallment();

            if (idx) {
                amount = parseFloat(self.secondaryPaymentAmount()).toFixed(2),
                selectInstallment = parseFloat(self.creditCardSecondaryInstallment());
            }

            if (selectInstallment >= 0) {
                getnetSetInterest.getnetInterest(amount, idx, selectInstallment);
            }
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
            fullScreenLoader.startLoader();
            this.getnetTokenizeCard();
            this.placeOrder();
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
                quoteId = quote.getQuoteId(),
                token;

            fullScreenLoader.startLoader();

            serviceUrl = urlBuilder.createUrl('/carts/mine/generate-credit-card-number-token', {});
            payload = {
                cartId: quoteId,
                cardNumber: {
                    card_number: cardNumber
                }
            };

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
                global: false,
                contentType: 'application/json',
                type: 'POST',
                async: false
            }).done(
                function (response) {
                    token = response[0].number_token;
                    self.creditCardNumberToken(token);
                    self.getnetTokenizeCardSecondary();
                    fullScreenLoader.stopLoader(true);
                }
            );
            fullScreenLoader.stopLoader(true);
        },

        /**
         * Get Tokenize Seconday
         * @returns {void}
         */
        getnetTokenizeCardSecondary() {
            var self = this,
                cardSecondaryNumber = this.creditCardSecondaryNumber().replace(/\D/g, ''),
                serviceUrl,
                payloadSecondary,
                quoteId = quote.getQuoteId(),
                token;

            serviceUrl = urlBuilder.createUrl('/carts/mine/generate-credit-card-number-token', {});
            payloadSecondary = {
                cartId: quoteId,
                cardNumber: {
                    card_number: cardSecondaryNumber
                }
            };

            if (!customer.isLoggedIn()) {
                serviceUrl = urlBuilder.createUrl('/guest-carts/:cartId/generate-credit-card-number-token', {
                    cartId: quoteId
                });
                payloadSecondary = {
                    cartId: quoteId,
                    cardNumber: {
                        card_number: cardSecondaryNumber
                    }
                };
            }

            $.ajax({
                url: urlFormatter.build(serviceUrl),
                data: JSON.stringify(payloadSecondary),
                global: false,
                contentType: 'application/json',
                type: 'POST',
                async: false
            }).done(
                function (response) {
                    token = response[0].number_token;
                    self.creditCardSecondaryNumberToken(token);
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
                    'cc_cardholder_name': this.creditCardHolderName(),
                    'cc_number': this.creditCardNumber().substr(-4),
                    'cc_type': this.creditCardType(),
                    'cc_cid': this.creditCardVerificationNumber(),
                    'cc_exp_month': this.creditCardExpMonth(),
                    'cc_exp_year': this.creditCardExpYear(),
                    'cc_installments': this.creditCardInstallment(),
                    'cc_holder_tax_document': this.creditCardHolderTaxDocument(),
                    'cc_holder_phone': this.creditCardHolderPhone(),
                    'cc_payment_first_amount': parseFloat(this.firstPaymentAmount()).toFixed(2),
                    'cc_secondary_number_token': this.creditCardSecondaryNumberToken(),
                    'cc_secondary_cardholder_name': this.creditCardSecondaryHolderName(),
                    'cc_secondary_number': this.creditCardSecondaryNumber().substr(-4),
                    'cc_secondary_type': this.creditCardSecondaryType(),
                    'cc_secondary_cid': this.creditCardSecondaryVerificationNumber(),
                    'cc_secondary_exp_month': this.creditCardSecondaryExpMonth(),
                    'cc_secondary_exp_year': this.creditCardSecondaryExpYear(),
                    'cc_secondary_installments': this.creditCardSecondaryInstallment()
                }
            };

            data['additional_data'] = _.extend(data['additional_data'], this.additionalData);
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
         * Get First Calculete instalments
         * @returns {Array}
         */
        getCalculeteInstallments() {
            return getnetInstallment.getInstallments(parseFloat(this.firstPaymentAmount()));
        },

        /**
         * Get First Calculete instalments
         * @returns {Array}
         */
        getSecondaryCalculeteInstallments() {
            return getnetInstallment.getInstallments(parseFloat(this.secondaryPaymentAmount()));
        },

        /**
         * Fraud Manager
         * @returns {Boolean}
         */
        FraudManager() {
            return window.checkoutConfig.payment[this.getCode()].fraud_manager;
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
        },

        /**
         * Get Formart First Amount
         * @returns {String}
         */
        getFormartFirstAmount() {
            return priceUtils.formatPrice(this.firstPaymentAmount(), quote.getPriceFormat());
        },

        /**
         * Get Format Secondary Amount
         * @returns {String}
         */
        getFormartSecondarytAmount() {
            return priceUtils.formatPrice(this.secondaryPaymentAmount(), quote.getPriceFormat());
        },

        /**
         * Get Formart Min Scale
         * @returns {String}
         */
        getFormartMinScale() {
            return priceUtils.formatPrice(this.minScale, quote.getPriceFormat());
        },

        /**
         * Get Formart Min Scale
         * @returns {String}
         */
        getFormartMaxScale() {
            return priceUtils.formatPrice(this.maxScale, quote.getPriceFormat());
        }
    });
});
