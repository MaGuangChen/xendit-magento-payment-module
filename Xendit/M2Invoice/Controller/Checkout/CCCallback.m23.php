<?php

namespace Xendit\M2Invoice\Controller\Checkout;

use Magento\Framework\App\CsrfAwareActionInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\App\Request\InvalidRequestException;
use Magento\Framework\Controller\ResultFactory;
use Magento\Sales\Model\Order;
use Xendit\M2Invoice\Enum\LogDNALevel;

class CCCallback extends ProcessHosted implements CsrfAwareActionInterface
{
    public function execute()
    {
        try {
            $post = $this->getRequest()->getContent();
            $callbackToken = $this->getRequest()->getHeader('X-CALLBACK-TOKEN');
            $decodedPost = json_decode($post, true);

            $orderIds = explode('-', $this->getRequest()->getParam('order_ids'));
            
            $shouldRedirect = 0;
            $isError = 0;
            $flag = 1;

            foreach ($orderIds as $key => $value) {
                $order = $this->getOrderFactory()->create();
                $order  ->load($value);

                $payment            = $order->getPayment();
                $quoteId            = $order->getQuoteId();
                $quote              = $this->getQuoteRepository()->get($quoteId);
                $additionalInfo     = $quote->getPayment()->getAdditionalInformation();

                if ($payment->getAdditionalInformation('xendit_hosted_payment_id') !== null) {
                    $requestData = [
                        'id' => $payment->getAdditionalInformation('xendit_hosted_payment_id'),
                        'hp_token' => $payment->getAdditionalInformation('xendit_hosted_payment_token')
                    ];
    
                    if ($flag) { // complete hosted payment only once as status will be changed to USED
                        $hostedPayment = $this->getCompletedHostedPayment($requestData);
                        $flag = false;
                    }
                    
                    if (isset($hostedPayment['error_code'])) {
                        $isError = 1;
                        $this->handlePaymentFailure($order, $hostedPayment['error_code'], $hostedPayment['error_code'] . ' - Error reconciliating', $shouldRedirect);
                    }
                    else {
                        if ($hostedPayment['paid_amount'] != $hostedPayment['amount']) {
                            $order->setBaseDiscountAmount($hostedPayment['paid_amount'] - $hostedPayment['amount']);
                            $order->setDiscountAmount($hostedPayment['paid_amount'] - $hostedPayment['amount']);
                            $order->save();
            
                            $order->setBaseGrandTotal($order->getBaseGrandTotal() + $order->getBaseDiscountAmount());
                            $order->setGrandTotal($order->getGrandTotal() + $order->getDiscountAmount());
                            $order->save();
                        }
        
                        $this->processSuccessfulTransaction(
                            $order,
                            $payment,
                            'Xendit Credit Card payment completed. Transaction ID: ',
                            $hostedPayment['charge_id'],
                            $shouldRedirect
                        );
                    }
                }
            }

            $result = $this->getJsonResultFactory()->create();

            if ($isError) {
                $result->setHttpResponseCode(\Magento\Framework\Webapi\Exception::HTTP_BAD_REQUEST);
                $result->setData([
                    'status' => __('ERROR'),
                    'message' => 'Callback error: ' . $hostedPayment['error_code']
                ]);
            } else {
                $result->setData([
                    'status' => __('OK'),
                    'message' => 'Callback processed successfully.'
                ]);
            }
            return $result;
            
        } catch (\Exception $e) {
            $result = $this->getJsonResultFactory()->create();
            $result->setHttpResponseCode(\Magento\Framework\Webapi\Exception::HTTP_BAD_REQUEST);
            $result->setData([
                'status' => __('ERROR'),
                'message' => $e->getMessage()
            ]);

            return $result;
        }
    }

    private function processSuccessfulTransaction($order, $payment, $paymentMessage, $transactionId, $shouldRedirect = 1)
    {
        $orderState = Order::STATE_PROCESSING;
        $order->setState($orderState)
            ->setStatus($orderState)
            ->addStatusHistoryComment("$paymentMessage $transactionId");

        $order->save();

        $payment->setTransactionId($transactionId);
        $payment->addTransaction(\Magento\Sales\Model\Order\Payment\Transaction::TYPE_CAPTURE, null, true);

        //$this->invoiceOrder($order, $transactionId); // ERROR HERE!

        $this->getMessageManager()->addSuccessMessage(__("Your payment with Xendit is completed"));
    }

    public function createCsrfValidationException(RequestInterface $request): ?InvalidRequestException
    {
        return null;
    }

    public function validateForCsrf(RequestInterface $request): ?bool
    {
        return true;
    }
}
