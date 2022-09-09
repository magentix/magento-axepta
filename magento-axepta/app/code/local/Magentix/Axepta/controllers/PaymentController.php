<?php
/**
 * Copyright (C) 2022 Magentix SARL
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 */

class Magentix_Axepta_PaymentController extends Mage_Core_Controller_Front_Action
{
    /**
     * Create the payment and redirect the customer to Axepta
     *
     * @return void
     * @throws Exception
     */
    public function createAction()
    {
        if (!$this->getPaymentHelper()->getMerchantId()) {
            $this->paymentError('The Merchant ID is missing');
            return;
        }

        if (!$this->getPaymentHelper()->getHMAC()) {
            $this->paymentError('The HMAC key is missing');
            return;
        }

        if (!$this->getPaymentHelper()->getCryptKey()) {
            $this->paymentError('The Crypt Key key is missing');
            return;
        }

        $order = $this->getCheckout()->getLastRealOrder();
        if (!$order->getId()) {
            $this->paymentError('The order is not found');
            return;
        }

        $totalToPay = $order->getGrandTotal() - $order->getTotalInvoiced();
        if (!$totalToPay) {
            $this->paymentError('Order already paid');
            return;
        }

        $transactionId = $this->getPaymentHelper()->generateKey();

        $order->setData(Magentix_Axepta_Helper_Data::ORDER_AXEPTA_KEY, $transactionId);
        $order->setStatus(Mage_Sales_Model_Order::STATE_PENDING_PAYMENT);

        $storeName = Mage::getStoreConfig('general/store_information/name', $order->getStore());
        $language  = substr(Mage::getStoreConfig('general/locale/code', $order->getStore()), 0, 2);

        $paymentHelper = $this->getPaymentHelper();
        $axeptaPayment = $this->getAxeptaPayment();

        $axeptaPayment->setSecretKey($this->getPaymentHelper()->getHMAC());
        $axeptaPayment->setCryptKey($this->getPaymentHelper()->getCryptKey());
        $axeptaPayment->setUrl(Magentix_Axepta_Model_Axepta::PAYSSL);
        $axeptaPayment->setMerchantId($this->getPaymentHelper()->getMerchantId());
        $axeptaPayment->setTransID($order->getIncrementId());
        $axeptaPayment->setAmount($this->getPaymentHelper()->formatAmount($totalToPay));
        $axeptaPayment->setCurrency($order->getOrderCurrencyCode());
        $axeptaPayment->setRefNr($order->getId());
        $axeptaPayment->setURLSuccess(Mage::getUrl('axepta/payment/success'));
        $axeptaPayment->setURLFailure(Mage::getUrl('axepta/payment/failure'));
        $axeptaPayment->setURLNotify(Mage::getUrl('axepta/payment/webhook'));
        $axeptaPayment->setURLBack(Mage::getUrl('axepta/payment/cancel', ['o' => $transactionId]));
        $axeptaPayment->setLanguage($language);
        $axeptaPayment->setOrderDesc($paymentHelper->getOrderDesc() ?: $storeName);
        $axeptaPayment->setMsgVer();
        $axeptaPayment->setResponseParam();

        $axeptaPayment->validate();
        $axeptaPayment->getShaSign();

        $orderPayment = $order->getPayment();
        $orderPayment->setTransactionId($transactionId);
        $orderPayment->addTransaction(
            Mage_Sales_Model_Order_Payment_Transaction::TYPE_CAPTURE,
            null,
            false,
            Mage::helper('axepta')->__(
                'Ordering amount of %s is pending approval on gateway.',
                $totalToPay
            )
        );
        $orderPayment->setLastTransId($transactionId);
        $orderPayment->save();

        /** @var Mage_Core_Helper_Data $coreHelper */
        $coreHelper = Mage::helper('core');

        $params = [
            'MerchantID'   => $this->getPaymentHelper()->getMerchantId(),
            'Data'         => $axeptaPayment->getBFishCrypt(),
            'Len'          => $axeptaPayment->getLen(),
            'URLBack'      => $axeptaPayment->getParam('URLBack'),
            'CustomField1' => $coreHelper->currencyByStore(
                (float)($axeptaPayment->getParam('Amount') / 100),
                $order->getStore(),
                true,
                false
            ),
            'CustomField2' => $storeName,
            'CustomField4' => $this->customFieldCart($order->getAllVisibleItems()),
        ];
        if ($logo = $this->getPaymentHelper()->getLogoUrl()) {
            $params['CustomField3'] = $logo;
        }
        if ($shippingAddress = $order->getShippingAddress()) {
            $params['CustomField6'] = $this->customFieldAddress($shippingAddress);
        }
        if ($billingAddress = $order->getBillingAddress()) {
            $params['CustomField7'] = $this->customFieldAddress($billingAddress);
        }

        $paymentUrl = $axeptaPayment->getUrl() . '?' . http_build_query($params);

        $order->setState(
            Mage_Sales_Model_Order::STATE_PENDING_PAYMENT,
            true,
            $this->__('Customer was redirect to payment gateway. Request: %s', $axeptaPayment->getDebug())
        );
        $order->save();

        if ($order->getQuoteId()) {
            $this->openQuote((int)$order->getQuoteId());
        }

        $this->_redirectUrl($paymentUrl);
    }

    /**
     * Webhook action to update the payment
     *
     * @return void
     */
    public function webhookAction()
    {
        try {
            $order = $this->getRequestedOrder();
            if (!$order) {
                throw new Exception('Order not found');
            }

            $axeptaPayment = $this->getAxeptaPayment();

            if (!$order->getPayment()) {
                throw new Exception('Payment is missing');
            }

            if ($axeptaPayment->isSuccessful()) {
                /** @var Mage_Sales_Model_Service_Order $service */
                $service = Mage::getModel('sales/service_order', $order);
                $invoice = $service->prepareInvoice();
                $invoice->setRequestedCaptureCase(Mage_Sales_Model_Order_Invoice::CAPTURE_ONLINE);
                $invoice->register();
                $invoice->getOrder()->setIsInProcess(true);

                /** @var Mage_Core_Model_Resource_Transaction $transaction */
                $transaction = Mage::getModel('core/resource_transaction');
                $transaction->addObject($invoice)->addObject($invoice->getOrder());
                $transaction->save();

                $order->getPayment()->pay($invoice);

                $order->setState(
                    Mage_Sales_Model_Order::STATE_PROCESSING,
                    true,
                    Mage::helper('axepta')->__(
                        'Payment approved by the gateway. PayID: %s',
                        $axeptaPayment->getParam('PayID')
                    )
                );
                $order->save();
                $order->sendNewOrderEmail();
            } else {
                $order->setState(
                    Mage_Sales_Model_Order::STATE_CANCELED,
                    true,
                    $this->__(
                        'The payment has been denied. PayID: %s. Error: %s',
                        $axeptaPayment->getParam('PayID'),
                        substr($axeptaPayment->getParam('Code'), -4) . ' - ' . $axeptaPayment->getParam('Description')
                    )
                );
                $order->save();
            }
        } catch (Exception $exception) {
            Mage::log($exception->getMessage(), Zend_Log::ERR, Magentix_Axepta_Helper_Data::AXEPTA_LOG_FILE);
            Mage::log($this->getGatewayRequest(), Zend_Log::ERR, Magentix_Axepta_Helper_Data::AXEPTA_LOG_FILE);
        }
    }

    /**
     * Payment Success
     *
     * @return void
     */
    public function successAction()
    {
        $order = $this->getRequestedOrder();
        if (!$order) {
            $this->_redirect('/');
            return;
        }

        try {
            $order->setState(
                Mage_Sales_Model_Order::STATE_PROCESSING,
                true,
                $this->__('Customer returned from payment gateway')
            );
            $order->save();
        } catch (Exception $exception) {
            Mage::log($exception->getMessage(), Zend_Log::ERR, Magentix_Axepta_Helper_Data::AXEPTA_LOG_FILE);
        }

        $checkout = $this->getCheckout();
        $checkout->setLastSuccessQuoteId($order->getQuoteId());
        $checkout->setLastQuoteId($order->getQuoteId());
        $checkout->setLastOrderId($order->getId());
        $this->closeQuote((int)$order->getQuoteId());

        $this->_redirect('checkout/onepage/success');
    }

    /**
     * Payment failure
     *
     * @return void
     */
    public function failureAction()
    {
        $order = $this->getRequestedOrder();
        $axeptaPayment = $this->getAxeptaPayment();

        try {
            Mage::getSingleton('core/session')->addError(
                $this->__(
                    'Your payment has failed: %s. Please try again or choose another payment method.',
                    $axeptaPayment->getParam('Description')
                )
            );

            if ($order) {
                if ($order->canCancel()) {
                    $order->setState(
                        Mage_Sales_Model_Order::STATE_CANCELED,
                        true,
                        $this->__(
                            'The payment has failed. PayID: %s. Error: %s',
                            $axeptaPayment->getParam('PayID'),
                            substr($axeptaPayment->getParam('Code'), -4) . ' - ' . $axeptaPayment->getParam('Description')
                        )
                    );
                    $order->save();
                }

                if ($order->getQuoteId()) {
                    $this->openQuote((int)$order->getQuoteId());
                }
            }
        } catch (Exception $exception) {
            Mage::getSingleton('core/session')->addError(
                $this->__('Your payment has failed. Please try again or choose another payment method.')
            );
            Mage::log($exception->getMessage(), Zend_Log::ERR, Magentix_Axepta_Helper_Data::AXEPTA_LOG_FILE);
            Mage::log($this->getGatewayRequest(), Zend_Log::ERR, Magentix_Axepta_Helper_Data::AXEPTA_LOG_FILE);
        }

        $this->_redirect('checkout/cart');
    }

    /**
     * Payment was canceled
     *
     * @return void
     */
    public function cancelAction()
    {
        $orderKey = $this->getRequest()->getParam('o');
        if (!$orderKey) {
            $this->_redirect('/');
            return;
        }

        /** @var Mage_Sales_Model_Order $orderModel */
        $orderModel = Mage::getModel('sales/order');
        $order = $orderModel->load($orderKey, Magentix_Axepta_Helper_Data::ORDER_AXEPTA_KEY);
        if (!$order->getId()) {
            $this->_redirect('/');
            return;
        }

        try {
            if ($order->canCancel()) {
                $order->setState(
                    Mage_Sales_Model_Order::STATE_CANCELED,
                    true,
                    $this->__('The payment was canceled by customer')
                );
                $order->save();
            }
        } catch (Exception $exception) {
            Mage::log($exception->getMessage(), Zend_Log::ERR, Magentix_Axepta_Helper_Data::AXEPTA_LOG_FILE);
        }

        if ($order->getQuoteId()) {
            $this->openQuote((int)$order->getQuoteId());
        }

        Mage::getSingleton('core/session')->addError(
            $this->__('Your payment has been canceled. Please try again or choose another payment method.')
        );

        $this->_redirect('checkout/cart');
    }

    /**
     * Validate Order
     *
     * @return Mage_Sales_Model_Order|false
     */
    protected function getRequestedOrder()
    {
        try {
            $axeptaPayment = $this->getAxeptaPayment();

            $axeptaPayment->setSecretKey($this->getPaymentHelper()->getHMAC());
            $axeptaPayment->setCryptKey($this->getPaymentHelper()->getCryptKey());
            $axeptaPayment->setResponse($this->getGatewayRequest());

            if (!$axeptaPayment->isValid()) {
                return false;
            }

            /** @var Mage_Sales_Model_Order $orderModel */
            $orderModel = Mage::getModel('sales/order');
            $order = $orderModel->load($axeptaPayment->getParam('refnr'));
            if (!$order->getId()) {
                return false;
            }

            return $order;
        } catch (Exception $exception) {
            Mage::log($exception->getMessage(), Zend_Log::ERR, Magentix_Axepta_Helper_Data::AXEPTA_LOG_FILE);
        }

        return false;
    }

    /**
     * Retrieve request with GET fallback
     *
     * @return array
     */
    protected function getGatewayRequest(): array
    {
        return empty($_POST) ? $_GET : $_POST;
    }

    /**
     * Close the quote
     *
     * @param int $quoteId
     *
     * @return void
     */
    protected function closeQuote(int $quoteId)
    {
        /** @var Mage_Sales_Model_Quote $quoteModel */
        $quoteModel = Mage::getModel('sales/quote');
        $quote = $quoteModel->load($quoteId);

        $quote->setIsActive(0);
        $quote->save();

        $this->getCheckout()->setQuoteId(null);
    }

    /**
     * Open a closed quote
     *
     * @param int $quoteId
     *
     * @return void
     */
    protected function openQuote(int $quoteId)
    {
        /** @var Mage_Sales_Model_Quote $quoteModel */
        $quoteModel = Mage::getModel('sales/quote');
        $quote = $quoteModel->load($quoteId);

        $quote->setIsActive(1);
        $quote->setReservedOrderId(null);
        $quote->save();

        $this->getCheckout()->replaceQuote($quote);
    }

    /**
     * Retrieve payment helper
     *
     * @return Magentix_Axepta_Helper_Data
     */
    protected function getPaymentHelper(): Magentix_Axepta_Helper_Data
    {
        /** @var Magentix_Axepta_Helper_Data $helper */
        $helper = Mage::helper('axepta');

        return $helper;
    }

    /**
     * Retrieve Axepta credit card payment
     *
     * @return Magentix_Axepta_Model_Axepta
     */
    protected function getAxeptaPayment(): Magentix_Axepta_Model_Axepta
    {
        /** @var Magentix_Axepta_Model_Axepta $model */
        $model = Mage::getSingleton('axepta/axepta');

        return $model;
    }

    /**
     * Send an error
     *
     * @param string $message
     *
     * @return void
     */
    protected function paymentError(string $message)
    {
        /** @var Mage_Core_Model_Session $session */
        $session = Mage::getSingleton('core/session');
        $session->addError($this->__($message));

        $this->_redirect('checkout/onepage');
    }

    /**
     * Retrieve checkout session
     *
     * @return Mage_Checkout_Model_Session
     */
    protected function getCheckout(): Mage_Checkout_Model_Session
    {
        /** @var Mage_Checkout_Model_Session $checkout */
        $checkout = Mage::getSingleton('checkout/session');

        return $checkout;
    }

    /**
     * Build custom field address
     *
     * @param Mage_Customer_Model_Address_Abstract $address
     *
     * @return string
     */
    protected function customFieldAddress(Mage_Customer_Model_Address_Abstract $address): string
    {
        $fields = [
            $address->getCompany(),
            $address->getFirstname() . ' ' . $address->getLastname(),
            $address->getStreet1(),
            $address->getStreet2(),
            $address->getPostcode() . ' ' . $address->getCity()
        ];
        $fields = array_filter($fields);
        foreach ($fields as $key => $field) {
            $fields[$key] = str_replace('|', '-', $field);
        }

        return join('|', $fields);
    }

    /**
     * Build custom field cart
     *
     * @param Mage_Sales_Model_Order_Item[] $items
     *
     * @return string
     */
    protected function customFieldCart(array $items): string
    {
        $fields = [];
        foreach ($items as $item) {
            $fields[] = 'x' . (int)$item->getQtyOrdered() . ' - ' . str_replace('|', '-', $item->getName());
        }

        return join('|', $fields);
    }
}
