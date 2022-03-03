/**
 * Copyright © Getnet. All rights reserved.
 *
 * @author    Bruno Elisei <brunoelisei@o2ti.com>
 * See LICENSE for license details.
 */
 define([
    'ko',
    'underscore',
    'jquery',
    'Magento_Checkout/js/model/quote',
    'Magento_Catalog/js/price-utils',
    'mage/translate'
], function (ko, _, $, quote, priceUtils, $t) {
    'use strict';

    return {
        totals: quote.getTotals(),

        /**
         * Get auxiliary code
         * @returns {String}
         */
        getAuxiliaryCode() {
            return 'getnet_paymentmagento_cc';
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
    };
});
