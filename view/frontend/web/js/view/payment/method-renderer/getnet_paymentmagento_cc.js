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
    'Magento_Payment/js/model/credit-card-validation/credit-card-data',
    'Getnet_PaymentMagento/js/view/payment/gateway/custom-validation',
    'Getnet_PaymentMagento/js/view/payment/lib/jquery/jquery.mask',
    'Magento_Checkout/js/model/quote',
    'Magento_Catalog/js/price-utils',
    'mage/translate',
    'ko',
    'Magento_Checkout/js/model/url-builder',
    'mage/url',
    'Magento_Customer/js/model/customer',
    'Magento_Payment/js/model/credit-card-validation/validator'
], function (_,
        $,
        Component,
        VaultEnabler,
        creditCardData,
        _customValidation,
        _mask,
        quote,
        priceUtils,
        $t,
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
            selectedCardType: ''
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
                'creditCardInstallment',
                'creditCardNumberToken',
                'creditCardholderName',
                'creditCardHolderTaxDocument',
                'creditCardHolderPhone'
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
            this.getTokenize();
            this.placeOrder();
        },

        /**
         * Get Tokenize
         * @returns {void}
         */
        getTokenize() {
            var self = this,
                cardNumber = this.creditCardNumber().replace(/\D/g, ''),
                serviceUrl,
                payload,
                quoteId = quote.getQuoteId(),
                token;

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
                } else {
                    serviceUrl = urlBuilder.createUrl('/carts/mine/generate-credit-card-number-token', {});
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
                    'cc_holder_phone': this.creditCardHolderPhone()
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
         * Fraud Manager
         * @returns {Boolean}
         */
        FraudManager() {
            return window.checkoutConfig.payment[this.getCode()].fraud_manager;
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

            info_interest = window.checkoutConfig.payment[this.getCode()].info_interest;

            min_installment = window.checkoutConfig.payment[this.getCode()].min_installment;

            max_installment = window.checkoutConfig.payment[this.getCode()].max_installment;

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
