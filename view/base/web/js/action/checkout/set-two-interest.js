/**
 * Copyright © Getnet. All rights reserved.
 *
 * @author    Bruno Elisei <brunoelisei@o2ti.com>
 * See LICENSE for license details.
 */

 define([
    'underscore',
    'jquery',
    'Magento_Checkout/js/action/get-totals',
    'Magento_Checkout/js/model/error-processor',
    'Magento_Checkout/js/model/quote',
    'Magento_Checkout/js/model/totals',
    'Magento_Checkout/js/model/url-builder',
    'Magento_Customer/js/model/customer',
    'mage/url'
], function (
    _,
    $,
    getTotalsAction,
    errorProcessor,
    quote,
    totals,
    urlBuilder,
    customer,
    urlFormatter
) {
        'use strict';

        return {

            totals: quote.getTotals(),

            /**
             * Add Getnet Interest in totals
             * @param {Float} amount
             * @param {Int} idx
             * @param {Int} installment
             * @returns {Void}
             */
            getnetInterest(
                amount,
                idx,
                installment
            ) {
                var serviceUrl,
                    hasInterest = false,
                    quoteId = quote.getQuoteId(),
                    payload = {
                        'amountForCalc': {
                            'getnet_amount_for_calc': amount
                        },
                        'idxInTwoCc': {
                            'getnet_idx_in_two_cc': idx
                        },
                        'twoCcInstallment': {
                            'getnet_two_cc_installment': installment
                        }
                    };

                if (this.totals() && totals.getSegment('getnet_interest_amount')) {
                    hasInterest = totals.getSegment('getnet_interest_amount').value;
                    if (!installment && !hasInterest) {
                        return this;
                    }
                }

                serviceUrl = urlBuilder.createUrl('/carts/mine/getnet-two-cc-interest/', {});

                if (!customer.isLoggedIn()) {
                    serviceUrl = urlBuilder.createUrl(
                        '/guest-carts/:cartId/getnet-two-cc-interest/',
                        {
                            cartId: quoteId
                        }
                    );
                }

                $.ajax({
                    url: urlFormatter.build(serviceUrl),
                    global: true,
                    data: JSON.stringify(payload),
                    contentType: 'application/json',
                    type: 'POST',
                    async: true
                }).done(
                    () => {
                        var deferred = $.Deferred();

                        getTotalsAction([], deferred);
                    }
                ).fail(
                    (response) => {
                        errorProcessor.process(response);
                    }
                );
            }
        };
});
