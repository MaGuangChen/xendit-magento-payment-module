<?php

namespace Xendit\M2Invoice\Model\Payment;

use Magento\Quote\Api\Data\CartInterface;

/**
 * Class Palawan
 * @package Xendit\M2Invoice\Model\Payment
 */
class Palawan extends AbstractInvoice
{
    /**
     * Payment Method feature
     *
     * @var bool
     */
    protected $_isInitializeNeeded = true;

    /**
     * Payment code
     *
     * @var string
     */
    protected $_code = 'palawan';
    protected $methodCode = 'Palawan';

    /**
     * @param CartInterface|null $quote
     * @return bool
     */
    public function isAvailable(CartInterface $quote = null)
    {
        if ($quote === null || !$this->isAvailableOnCurrency()) {
            return false;
        }

        $amount = ceil($quote->getSubtotal() + $quote->getShippingAddress()->getShippingAmount());
        if ($amount < $this->dataHelper->getPaymentMinOrderAmount($this->_code) || $amount > $this->dataHelper->getPaymentMaxOrderAmount($this->_code)) {
            return false;
        }

        if (!$this->dataHelper->getIsPaymentActive($this->_code) || !$this->dataHelper->getIsActive()) {
            return false;
        }

        return true;
    }
}
