	
define(
    [
        'uiComponent',
        'Magento_Checkout/js/model/payment/renderer-list'
    ],
    function (
        Component,
        rendererList
    ) {
        'use strict';
        rendererList.push(
            {
                type: 'mandiriva',
                component: 'Xendit_M2Invoice/js/view/payment/method-renderer/mandiriva'
            }
        );
        return Component.extend({});
    }
);