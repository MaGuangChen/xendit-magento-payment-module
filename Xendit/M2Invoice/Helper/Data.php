<?php

namespace Xendit\M2Invoice\Helper;

use Magento\Catalog\Model\Product;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Customer\Model\CustomerFactory;
use Magento\Framework\ObjectManagerInterface;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\DataObject;
use Magento\Framework\Filesystem\Driver\File;
use Magento\Framework\UrlInterface;
use Magento\Quote\Model\QuoteFactory;
use Magento\Quote\Model\QuoteManagement;
use Magento\Store\Model\StoreManagerInterface;
use Xendit\M2Invoice\Model\Payment\M2Invoice;

class Data extends AbstractHelper
{
    private $objectManager;

    private $storeManager;

    private $m2Invoice;

    private $fileSystem;

    private $product;

    private $customerRepository;

    private $customerFactory;

    private $quote;

    private $quoteManagement;

    public function __construct(
        ObjectManagerInterface $objectManager,
        Context $context,
        StoreManagerInterface $storeManager,
        M2Invoice $m2Invoice,
        File $fileSystem,
        Product $product,
        CustomerRepositoryInterface $customerRepository,
        CustomerFactory $customerFactory,
        QuoteFactory $quote,
        QuoteManagement $quoteManagement
    ) {
        $this->objectManager = $objectManager;
        $this->storeManager = $storeManager;
        $this->m2Invoice = $m2Invoice;
        $this->fileSystem = $fileSystem;
        $this->product = $product;
        $this->customerRepository = $customerRepository;
        $this->customerFactory = $customerFactory;
        $this->quote = $quote;
        $this->quoteManagement = $quoteManagement;

        parent::__construct($context);
    }

    protected function getStoreManager()
    {
        return $this->storeManager;
    }

    public function getCheckoutUrl()
    {
        return $this->m2Invoice->getConfigData('xendit_url');
    }

    public function getUiUrl()
    {
        return $this->m2Invoice->getUiUrl();
    }

    public function getSuccessUrl($isMultishipping = false)
    {
        $baseUrl = $this->getStoreManager()->getStore()->getBaseUrl() . 'xendit/checkout/success';
        if ($isMultishipping) {
            $baseUrl .= '?type=multishipping';
        }

        return $baseUrl;
    }

    public function getFailureUrl($orderId, $isMultishipping = false)
    {
        $baseUrl = $this->getStoreManager()->getStore()->getBaseUrl() . "xendit/checkout/failure?order_id=$orderId";
        if ($isMultishipping) {
            $baseUrl .= '&type=multishipping';
        }
        return $baseUrl;
    }

    public function getThreeDSResultUrl($orderId, $isMultishipping = false)
    {
        $baseUrl = $this->getStoreManager()->getStore()->getBaseUrl() . "xendit/checkout/threedsresult?order_id=$orderId";
        if ($isMultishipping) {
            $baseUrl .= "&type=multishipping";
        }
        return $baseUrl;
    }

    public function getExternalId($orderId, $duplicate = false)
    {
        $defaultExtId = $this->getExternalIdPrefix() . "-$orderId";

        if ($duplicate) {
            return uniqid() . "-" . $defaultExtId;
        }

        return $defaultExtId;
    }

    public function getExternalIdPrefix()
    {
        return $this->m2Invoice->getConfigData('external_id_prefix') . "-" . $this->getStoreName();
    }

    public function getStoreName()
    {
        return substr(preg_replace("/[^a-z0-9]/mi", "", $this->getStoreManager()->getStore()->getName()), 0, 20);
    }

    public function getApiKey()
    {
        return $this->m2Invoice->getApiKey();
    }

    public function getPublicApiKey()
    {
        return $this->m2Invoice->getPublicApiKey();
    }

    public function getSubscriptionInterval()
    {
        return $this->m2Invoice->getSubscriptionInterval() ?: 'MONTH';
    }

    public function getSubscriptionIntervalCount()
    {
        return $this->m2Invoice->getSubscriptionIntervalCount() ?: 1;
    }

    public function getEnvironment()
    {
        return $this->m2Invoice->getEnvironment();
    }

    public function getCardPaymentType()
    {
        return $this->m2Invoice->getCardPaymentType();
    }

    public function getAllowedMethod()
    {
        return $this->m2Invoice->getAllowedMethod();
    }

    public function getChosenMethods()
    {
        return $this->m2Invoice->getChosenMethods();
    }

    public function getEnabledPromo()
    {
        return $this->m2Invoice->getEnabledPromo();
    }

    public function getIsActive()
    {
        return $this->m2Invoice->getIsActive();
    }

    public function getSendInvoiceEmail()
    {
        return $this->m2Invoice->getSendInvoiceEmail();
    }

    public function jsonData()
    {
        $inputs = json_decode((string) $this->fileSystem->fileGetContents((string)'php://input'), (bool) true);
        $methods = $this->_request->getServer('REQUEST_METHOD');
        
        if (empty($inputs) === true && $methods === 'POST') {
            $post = $this->_request->getPostValue();
                       
            if (array_key_exists('payment', $post)) {
                $inputs['paymentMethod']['additional_data'] = $post['payment'];
            }
        }

        return (array) $inputs;
    }

    public function getXenditSubscriptionCallbackUrl($isMultishipping = false) {
        $baseUrl = $this->getStoreManager()->getStore()->getBaseUrl(UrlInterface::URL_TYPE_LINK) . 'xendit/checkout/subscriptioncallback';

        if ($isMultishipping) {
            $baseUrl .= '?type=multishipping';
        }

        return $baseUrl;
    }

    /**
     * Map card's failure reason to more detailed explanation based on current insight.
     *
     * @param $failureReason
     * @return string
     */
    public function failureReasonInsight($failureReason)
    {
        switch ($failureReason) {
            case 'CARD_DECLINED':
            case 'STOLEN_CARD': return 'The bank that issued this card declined the payment but didn\'t tell us why.
                Try another card, or try calling your bank to ask why the card was declined.';
            case 'INSUFFICIENT_BALANCE': return "Your bank declined this payment due to insufficient balance. Ensure
                that sufficient balance is available, or try another card";
            case 'INVALID_CVN': return "Your bank declined the payment due to incorrect card details entered. Try to
                enter your card details again, including expiration date and CVV";
            case 'INACTIVE_CARD': return "This card number does not seem to be enabled for eCommerce payments. Try
                another card that is enabled for eCommerce, or ask your bank to enable eCommerce payments for your card.";
            case 'EXPIRED_CARD': return "Your bank declined the payment due to the card being expired. Please try
                another card that has not expired.";
            case 'PROCESSOR_ERROR': return 'We encountered issue in processing your card. Please try again with another card';
            case 'USER_DID_NOT_AUTHORIZE_THE_PAYMENT':
                return 'Please complete the payment request within 60 seconds.';
            case 'USER_DECLINED_THE_TRANSACTION':
                return 'You rejected the payment request, please try again when needed.';
            case 'PHONE_NUMBER_NOT_REGISTERED':
                return 'Your number is not registered in OVO, please register first or contact OVO Customer Service.';
            case 'EXTERNAL_ERROR':
                return 'There is a technical issue happens on OVO, please contact the merchant to solve this issue.';
            case 'SENDING_TRANSACTION_ERROR':
                return 'Your transaction is not sent to OVO, please try again.';
            case 'EWALLET_APP_UNREACHABLE':
                return 'Do you have OVO app on your phone? Please check your OVO app on your phone and try again.';
            case 'REQUEST_FORBIDDEN_ERROR':
                return 'Your merchant disable OVO payment from his side, please contact your merchant to re-enable it
                    before trying it again.';
            case 'DEVELOPMENT_MODE_PAYMENT_ACKNOWLEDGED':
                return 'Development mode detected. Please refer to our documentations for successful payment
                    simulation';
            default: return $failureReason;
        }
    }

    /**
     * Map Magento sales rule action to Xendit's standard type
     *
     * @param $type
     * @return string
     */
    public function mapSalesRuleType($type)
    {
        switch ($type) {
            case 'to_percent':
            case 'by_percent':
                return 'PERCENTAGE';
            case 'to_fixed':
            case 'by_fixed':
                return 'FIXED';
            default:
                return $type;
        }
    }
    
    public function xenditPaymentMethod( $payment ){
        
        //method name => frontend routing
        $listPayment = [
            "cc" => "cc",
            "cchosted" => "cchosted",
            "cc_installment" => "cc_installment",
            "cc_subscription" => "cc_subscription",
            "bcava" => "bca",
            "bniva" => "bni",
            "briva" => "bri",
            "mandiriva" => "mandiri",
            "permatava" => "permata",
            "alfamart" => "alfamart",
            "ovo" => "ovo"
        ];

        $response = FALSE;
        if( !!array_key_exists($payment, $listPayment) ){
            $response = $listPayment[$payment];
        }

        return $response; 
    }

    /**
     * Create Order Programatically
     * 
     * @param array $orderData
     * @return array
     * 
    */
    public function createMageOrder($orderData) {
        $store = $this->getStoreManager()->getStore();
        $websiteId = $this->getStoreManager()->getStore()->getWebsiteId();

        $customer = $this->customerFactory->create();
        $customer->setWebsiteId($websiteId);
        $customer->loadByEmail($orderData['email']); //load customer by email address
        
        if(!$customer->getEntityId()){
            //if not available then create this customer 
            $customer->setWebsiteId($websiteId)
                     ->setStore($store)
                     ->setFirstname($orderData['shipping_address']['firstname'])
                     ->setLastname($orderData['shipping_address']['lastname'])
                     ->setEmail($orderData['email']) 
                     ->setPassword($orderData['email']);
            $customer->save();
        }

        $quote = $this->quote->create(); //create object of quote
        $quote->setStore($store);
        
        $customer= $this->customerRepository->getById($customer->getEntityId());
        $quote->setCurrency();
        $quote->assignCustomer($customer); //assign quote to customer
 
        //add items in quote
        foreach($orderData['items'] as $item){
            $_product = $this->objectManager->create(\Magento\Catalog\Model\Product::class);
            $product = $_product->load($item['product_id']);
            $product->setPrice($item['price']);

            $normalizedProductRequest = array_merge(
                ['qty' => intval($item['qty'])],
                array()
            );
            $quote->addProduct(
                $product,
                new DataObject($normalizedProductRequest)
            );
        }
 
        //set address
        $quote->getBillingAddress()->addData($orderData['billing_address']);
        $quote->getShippingAddress()->addData($orderData['shipping_address']);
 
        //collect rates, set shipping & payment method
        $billingAddress = $quote->getBillingAddress();
        $shippingAddress = $quote->getShippingAddress();

        $shippingAddress->setShippingMethod($orderData['shipping_method'])
                        ->setCollectShippingRates(true)
                        ->collectShippingRates();
       
        $billingAddress->setShouldIgnoreValidation(true);
        $shippingAddress->setShouldIgnoreValidation(true);

        $quote->collectTotals();
        $quote->setIsMultiShipping($orderData['is_multishipping']);

        if (!$quote->getIsVirtual()) {
            if (!$billingAddress->getEmail()) {
                $billingAddress->setSameAsBilling(1);
            }
        }

        $quote->setPaymentMethod($orderData['payment']['method']);
        $quote->setInventoryProcessed(true); //update inventory
        $quote->save();
        
        //set required payment data
        $orderData['payment']['cc_number'] = str_replace('X', '0', $orderData['masked_card_number']);
        $quote->getPayment()->importData($orderData['payment']);

        foreach($orderData['payment']['additional_information'] AS $key=>$value) {
            $quote->getPayment()->setAdditionalInformation($key, $value);
        }
        $quote->getPayment()->setAdditionalInformation('xendit_is_subscription', true);

        //collect totals & save quote
        $quote->collectTotals()->save();
 
        //create order from quote
        $order = $this->quoteManagement->submit($quote);

        //update order status
        $orderState = \Magento\Sales\Model\Order::STATE_PROCESSING;
        $message = "Xendit subscription payment completed. Transaction ID: " . $orderData['transaction_id'] . ". ";
        $message .= "Original Order: #" . $orderData['parent_order_id'] . ".";
        $order->setState($orderState)
              ->setStatus($orderState)
              ->addStatusHistoryComment($message);

        $order->save();

        //save order payment details
        $payment = $order->getPayment();
        $payment->setTransactionId($orderData['transaction_id']);
        $payment->addTransaction(\Magento\Sales\Model\Order\Payment\Transaction::TYPE_CAPTURE, null, true);

        //create invoice
        if ($order->canInvoice()) {
            $invoice = $this->objectManager->create('Magento\Sales\Model\Service\InvoiceService')
                                           ->prepareInvoice($order);
            
            if ($invoice->getTotalQty()) {
                $invoice->setTransactionId($orderData['transaction_id']);
                $invoice->setRequestedCaptureCase(\Magento\Sales\Model\Order\Invoice::CAPTURE_OFFLINE);
                $invoice->register();
                $invoice->setState(\Magento\Sales\Model\Order\Invoice::STATE_PAID)->save();

                $transaction = $this->objectManager->create('Magento\Framework\DB\Transaction')
                                                   ->addObject($invoice)
                                                   ->addObject($invoice->getOrder());
                $transaction->save();
            }
        }

        //notify customer
        $this->objectManager->create('Magento\Sales\Model\OrderNotifier')->notify($order);
        $order->setEmailSent(1);
        $order->save();

        if($order->getEntityId()){
            $result['order_id'] = $order->getRealOrderId();
        }else{
            $result = array('error' => 1, 'msg' => 'Error creating order');
        }

        return $result;
    }
}
