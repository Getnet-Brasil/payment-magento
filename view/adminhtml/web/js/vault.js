/**
 * Copyright © Getnet. All rights reserved.
 *
 * @author    Bruno Elisei <brunoelisei@o2ti.com>
 * See LICENSE for license details.
 */
/*browser:true*/
// eslint-disable-next-line no-redeclare
/*global define*/
define([
    'jquery',
    'uiComponent'
], function ($, Class) {
    'use strict';

    return Class.extend({
        defaults: {
            $selector: null,
            selector: 'edit_form'
        },

        /**
         * Initializes model instance.
         *
         * @returns {Object}
         */
        initObservable: function () {
            var self = this;

            self.$selector = $('#' + self.selector);
            this._super();

            this.initEventHandlers();
            return this;
        },

        /**
         * Get code
         * @returns {String}
         */
        getCode: function () {
            return this.code;
        },

        /**
         * Init event handlers
         * @returns {jquery}
         */
        initEventHandlers: function () {
            $('#' + this.container).find('[name="payment[token_switcher]"]')
                .on('click', this.setPaymentDetails.bind(this));
        },

        /**
         * Set payment details
         * @returns {void}
         */
        setPaymentDetails: function () {
            this.$selector.find('[name="payment[public_hash]"]').val(this.publicHash);
        }
    });
});
