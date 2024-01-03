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
    'Magento_Checkout/js/model/totals',
    'Magento_Catalog/js/price-utils',
    'mage/translate'
], function (ko, _, $, quote, totals, priceUtils, $t) {
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
         * @param   {Float|null} calcBy
         * @returns {Object}
         */
        getInstalmentsValues(calcBy = null) {
            var grandTotal,
                info_interest,
                min_installment,
                max_installment,
                installmentsCalcValues = {},
                max_div,
                limit,
                idx,
                interest,
                interestTotal,
                installment,
                totalInstallment,
                taxa,
                previewInterest = 0;

            grandTotal = calcBy;
            if (!calcBy) {
                if (this.totals() && totals.getSegment('getnet_interest_amount')) {
                    previewInterest = totals.getSegment('getnet_interest_amount').value;
                }
                grandTotal = quote.totals().base_grand_total - previewInterest;
            }
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
                    interestTotal = grandTotal * Math.pow((1 + taxa), idx) - grandTotal;
                    installment = (grandTotal + interestTotal) / idx;
                    totalInstallment = interestTotal + grandTotal;
                    if (installment > 5 && installment > min_installment) {
                        installmentsCalcValues[idx] = {
                            'installment': priceUtils.formatPrice(installment, quote.getPriceFormat()),
                            'totalInstallment': priceUtils.formatPrice(totalInstallment, quote.getPriceFormat()),
                            // eslint-disable-next-line max-len
                            'totalInterest': priceUtils.formatPrice(interestTotal, quote.getPriceFormat()),
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
         * @param   {Float|null} calcBy
         * @returns {Array}
         */
        getInstallments(calcBy = null) {
            var temp,
                inst,
                newArray = [],
                idx;

            temp = _.map(this.getInstalmentsValues(calcBy), function (value, key) {
                // eslint-disable-next-line max-len
                inst = $t('%1x of %2 in the total value of %3').replace('%1', key).replace('%2', value['installment']).replace('%3', value['totalInstallment']);

                if (value['interest'] === 0) {
                    inst = $t('%1x of %2 not interest').replace('%1', key).replace('%2', value['installment']);
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
