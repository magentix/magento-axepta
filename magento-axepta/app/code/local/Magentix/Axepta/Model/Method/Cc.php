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

class Magentix_Axepta_Model_Method_Cc extends Mage_Payment_Model_Method_Abstract
{
    protected $_code = 'axepta';

    protected $_formBlockType = 'axepta/form';

    protected $_isGateway = false;

    protected $_canAuthorize = false;

    protected $_canCapture = false;

    protected $_canCapturePartial = false;

    protected $_canRefund = false;

    protected $_canRefundInvoicePartial = false;

    protected $_canVoid = false;

    protected $_canUseInternal = true;

    protected $_canUseCheckout = true;

    protected $_canUseForMultishipping = false;

    protected $_isInitializeNeeded = false;

    /**
     * Retrieve redirect payment URL
     *
     * @return string
     */
    public function getOrderPlaceRedirectUrl(): string
    {
        return Mage::getUrl('axepta/payment/create');
    }

    /**
     * Check whether payment method can be used
     *
     * @param Mage_Sales_Model_Quote|null $quote
     *
     * @return bool
     */
    public function isAvailable($quote = null): bool
    {
        if (!parent::isAvailable($quote)) {
            return false;
        }
        if (!$quote) {
            return false;
        }

        /** @var Magentix_Axepta_Helper_Data $helper */
        $helper = Mage::helper('axepta');

        return $helper->isAllowed($quote->getCustomerEmail());
    }
}
