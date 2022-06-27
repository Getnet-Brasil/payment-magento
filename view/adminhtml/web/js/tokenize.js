/**
 * Copyright © Getnet. All rights reserved.
 *
 * @author    Bruno Elisei <brunoelisei@o2ti.com>
 * See LICENSE for license details.
 */
define([
	'underscore',
	'jquery',
	'mage/url'
], function (_, $) {
	'use strict';

    var $el = jQuery('#edit_form'),
		$elPayment = jQuery('#payment_form_getnet_paymentmagento_cc'),
        config,
		url;

    if (!$el.length || !$el.data('order-config')) {
        return;
    }
	config = $el.data('order-config');

	url = $elPayment.data('url-to-tokenize');

	function setTokenize() {
		var Cc = $('#getnet_paymentmagento_cc_number').val(),
			payload = {
				form_key: window.FORM_KEY,
				cardNumber: Cc,
				storeId: config.store_id
			},
			token;

		$.ajax({
			url: url,
			data: payload,
			contentType: 'application/json',
			type: 'GET'
		}).done(
			function (response) {
				token = response[0].tokenize.number_token;
				$('#getnet_paymentmagento_cc_number_token').val(token);
			}
		);
	}

	$('#getnet_paymentmagento_cc_number').change(function () {
		setTokenize();
	});
});
