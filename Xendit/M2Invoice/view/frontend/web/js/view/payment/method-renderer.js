
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
                type: 'bcava',
                component: 'Xendit_M2Invoice/js/view/payment/method-renderer/bcava'
            },
            {
                type: 'alfamart',
                component: 'Xendit_M2Invoice/js/view/payment/method-renderer/alfamart'
            },
            {
                type: 'bniva',
                component: 'Xendit_M2Invoice/js/view/payment/method-renderer/bniva'
            },
            {
                type: 'briva',
                component: 'Xendit_M2Invoice/js/view/payment/method-renderer/briva'
            },
            {
                type: 'mandiriva',
                component: 'Xendit_M2Invoice/js/view/payment/method-renderer/mandiriva'
            },
            {
                type: 'permatava',
                component: 'Xendit_M2Invoice/js/view/payment/method-renderer/permatava'
            },
            {
                type: 'ovo',
                component: 'Xendit_M2Invoice/js/view/payment/method-renderer/ovo'
            },
            {
                type: 'shopeepay',
                component: 'Xendit_M2Invoice/js/view/payment/method-renderer/shopeepay'
            },
            {
                type: 'dana',
                component: 'Xendit_M2Invoice/js/view/payment/method-renderer/dana'
            },
            {
                type: 'linkaja',
                component: 'Xendit_M2Invoice/js/view/payment/method-renderer/linkaja'
            },
            {
                type: 'indomaret',
                component: 'Xendit_M2Invoice/js/view/payment/method-renderer/indomaret'
            },
            {
                type: 'cc',
                component: 'Xendit_M2Invoice/js/view/payment/method-renderer/cc'
            },
            {
                type: 'cc_subscription',
                component: 'Xendit_M2Invoice/js/view/payment/method-renderer/cc_subscription'
            },
            {
                type: 'qris',
                component: 'Xendit_M2Invoice/js/view/payment/method-renderer/qris'
            },
            {
                type: 'dd_bri',
                component: 'Xendit_M2Invoice/js/view/payment/method-renderer/dd_bri'
            },
            {
                type: 'kredivo',
                component: 'Xendit_M2Invoice/js/view/payment/method-renderer/kredivo'
            },
            {
                type: 'gcash',
                component: 'Xendit_M2Invoice/js/view/payment/method-renderer/gcash'
            },
            {
                type: 'grabpay',
                component: 'Xendit_M2Invoice/js/view/payment/method-renderer/grabpay'
            },
            {
                type: 'paymaya',
                component: 'Xendit_M2Invoice/js/view/payment/method-renderer/paymaya'
            }
        );
        return Component.extend({});
    }
);
