/**
 * Copyright © Getnet. All rights reserved.
 *
 * @author    Bruno Elisei <brunoelisei@o2ti.com>
 * See LICENSE for license details.
 */

 define(
    [
        'Magento_Checkout/js/view/summary/abstract-total',
        'Magento_Checkout/js/model/quote',
        'Magento_Catalog/js/price-utils',
        'Magento_Checkout/js/model/totals',
        'mage/translate'
    ],
    function (Component, quote, priceUtils, totals, $t) {
        'use strict';

        return Component.extend({
            defaults: {
                template: 'Getnet_PaymentMagento/cart/summary/getnet_interest',
                active: false
            },
            totals: quote.getTotals(),

            /**
             * Init observable variables
             *
             * @return {Object}
             */
            initObservable() {
                this._super().observe(['active']);
                return this;
            },

            /**
             * Is Active
             * @return {*|Boolean}
             */
            isActive() {
                return this.getPureValue() !== 0;
            },

            /**
             * Get Pure Value
             * @return {*}
             */
            getPureValue() {
                var getnetInterest = 0;

                if (this.totals() && totals.getSegment('getnet_interest_amount')) {
                    getnetInterest = totals.getSegment('getnet_interest_amount').value;
                    return getnetInterest;
                }

                return getnetInterest;
            },

            /**
             * Custon Title
             * @return {*|String}
             */
            customTitle() {
                if (this.getPureValue() > 0) {
                    return $t('Installments Interest');
                }
                return $t('Discount in cash');
            },

            /**
             * Get Value
             * @return {*|String}
             */
            getValue() {
                return this.getFormattedPrice(this.getPureValue());
            }
        });
    }
);
