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

class Magentix_Axepta_Helper_Data extends Mage_Core_Helper_Abstract
{
    const ORDER_AXEPTA_KEY = 'axepta_key';

    const AXEPTA_LOG_FILE = 'axepta.log';

    /**
     * Retrieve Merchant ID
     *
     * @return string|null
     */
    public function getMerchantId(): ?string
    {
        return Mage::getStoreConfig('payment/axepta/merchant_id');
    }

    /**
     * Retrieve HMAC
     *
     * @return string|null
     */
    public function getHMAC(): ?string
    {
        return Mage::getStoreConfig('payment/axepta/hmac_key');
    }

    /**
     * Retrieve Crypt key
     *
     * @return string|null
     */
    public function getCryptKey(): ?string
    {
        return Mage::getStoreConfig('payment/axepta/crypt_key');
    }

    /**
     * Customer is allowed
     *
     * @param mixed email
     *
     * @return bool
     */
    public function isAllowed($email): bool
    {
        $allowed = Mage::getStoreConfig('payment/axepta/allowed_emails');
        if (!$allowed) {
            return true;
        }
        if (empty($email)) {
            return false;
        }

        $emails = explode(',', $allowed);
        foreach ($emails as $value) {
            if (strtolower(trim($value)) === strtolower((string)$email)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Format amount
     *
     * @param mixed $amount
     *
     * @return int
     */
    public function formatAmount($amount): int
    {
        return (int)((float)$amount * 100);
    }

    /**
     * Generate a random and unique key
     *
     * @return string
     */
    public function generateKey(): string
    {
        /** @var Mage_Core_Helper_Data $helper */
        $helper = Mage::helper('core');

        return $helper->uniqHash(uniqid());
    }
}
