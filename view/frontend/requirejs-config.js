/**
 * Copyright © Getnet. All rights reserved.
 * @author    Bruno Elisei <brunoelisei@o2ti.com>
 * See LICENSE for license details.
 */
var config = {
    config: {
        mixins: {
            'Magento_Payment/js/model/credit-card-validation/credit-card-number-validator/credit-card-type': {
                'Getnet_PaymentMagento/js/mixins/credit-card-type-mixin': true
            }
        }
    }
};
