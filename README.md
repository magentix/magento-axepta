# Magento Axepta Payment

This module adds the payment solution BNP Paribas Axepta to Magento 1. 

The customer is redirected to Axepta payment gateway to make the Payment.

**Note:** Doesn't work with **Strict** value for frontend cookie **SameSite** attribute. The customer will be disconnected when returning to the merchant website. Use **Lax** or **None** only.

**From October 15, 2022, 3DSV2 is required. Please upgrade to 1.1.0 or higher.**

## Requirements

- Magento 1.9.X / OpenMage LTS
- PHP >= 7.2

## Installation

1. Download the latest release from module repository

2. Copy all the files from `magento-axepta` directory to your Magento root directory

## Configuration

Go to **System > Configuration > Payment Methods > Axepta**

- **Enabled**: Enabled Axepta payment method
- **Sort Order**: The payment method sort order
- **Title**: Payment title
- **Merchant ID**: Your merchant Identifier (MID)
- **HMAC Key**: Your HMAC Key
- **Blowfish Crypt Key**: Your Blowfish encryption key
- **Order Desc**: Custom order description. Set "Test:0000" for a simulation in production.
- **Allowed for emails only**: Enabled the method only for quote with specific customer emails (comma separated). Leave blank for no restriction.
- **Payment from Applicable Countries**: Allowed countries (all or specific countries)