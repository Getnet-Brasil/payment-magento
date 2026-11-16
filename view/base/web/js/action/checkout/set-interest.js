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
             * Grand total (without the Getnet interest) the stored interest was priced on.
             */
            pricedTotal: null,

            /**
             * Grand total without the Getnet interest.
             * @returns {String}
             */
            getTotalWithoutInterest() {
                var current = quote.totals() || {},
                    interest = 0;

                (current.total_segments || []).forEach((segment) => {
                    if (segment.code === 'getnet_interest_amount') {
                        interest = parseFloat(segment.value || 0);
                    }
                });

                return (parseFloat(current.base_grand_total || 0) - interest).toFixed(2);
            },

            /**
             * Whether the cart total changed (coupon, shipping) since the selected installment was priced.
             * Place order is refused by the server while the stored interest does not match.
             * @param {Int} installmentSelected
             * @returns {Boolean}
             */
            needsReprice(installmentSelected) {
                return parseInt(installmentSelected, 10) > 0 &&
                    this.pricedTotal !== null &&
                    this.pricedTotal !== this.getTotalWithoutInterest();
            },

            /**
             * Add Getnet Interest in totals
             * @param {Int} installmentSelected
             * @returns {Void}
             */
            getnetInterest(
                installmentSelected
            ) {
                var serviceUrl,
                    hasInterest = false,
                    quoteId = quote.getQuoteId(),
                    payload = {
                        'installmentSelected': {
                            'installment_selected': installmentSelected
                        }
                    };

                if (this.totals() && totals.getSegment('getnet_interest_amount')) {
                    hasInterest = totals.getSegment('getnet_interest_amount').value;
                    if (!installmentSelected && !hasInterest) {
                        return $.Deferred().resolve().promise();
                    }
                }

                this.pricedTotal = this.getTotalWithoutInterest();

                serviceUrl = urlBuilder.createUrl('/carts/mine/getnet-interest/', {});

                if (!customer.isLoggedIn()) {
                    serviceUrl = urlBuilder.createUrl(
                        '/guest-carts/:cartId/getnet-interest/',
                        {
                            cartId: quoteId
                        }
                    );
                }

                // Resolves only after the totals were refreshed, so callers can rely on the final grand total.
                return $.ajax({
                    url: urlFormatter.build(serviceUrl),
                    global: true,
                    data: JSON.stringify(payload),
                    contentType: 'application/json',
                    type: 'POST',
                    async: true
                }).then(
                    () => {
                        var deferred = $.Deferred();

                        getTotalsAction([], deferred);

                        return deferred.promise();
                    }
                ).fail(
                    (response) => {
                        errorProcessor.process(response);
                    }
                );
            }
        };
});
