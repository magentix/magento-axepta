<?php
/**
 * Copyright (C) 2022 Magentix SARL
 *
 * This class is a fork of the Aphania Axepta Access program <https://github.com/aphania/axepta-access>
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

class Magentix_Axepta_Model_Axepta
{
    public const PAYSSL = 'https://paymentpage.axepta.bnpparibas/payssl.aspx';

    public const DIRECT = 'https://paymentpage.axepta.bnpparibas/direct.aspx';

    public const CREDIT = 'https://paymentpage.axepta.bnpparibas/credit.aspx';

    public const DATA_FIELD = 'Data';

    protected $secretKey;

    protected $cryptKey;

    protected $pspURL = self::PAYSSL;

    protected $parameters = [];

    protected $pspFields = [
        'Debug',
        'PayID',
        'TransID',
        'MerchantID',
        'Amount',
        'Currency',
        'MAC',
        'RefNr',
        'Amount3D',
        'URLSuccess',
        'URLFailure',
        'URLNotify',
        'Response',
        'UserData',
        'Capture',
        'OrderDesc',
        'ReqID',
        'Plain',
        'Custom',
        'expirationTime',
        'AccVerify',
        'RTF',
        'ChDesc',
        'Len',
        'Data',
        'Template',
        'Language',
        'Background',
        'URLBack',
        'CCSelect',
        'MID',
        'mid',
        'refnr',
        'XID',
        'Status',
        'Description',
        'Code',
        'PCNr',
        'CCNr',
        'CCCVC',
        'CCBrand',
        'CCExpiry',
        'TermURL',
        'UserAgent',
        'HTTPAccept',
        'AboID',
        'ACSXID',
        'MaskedPan',
        'CAVV',
        'ECI',
        'DDD',
        'Type',
        'Plain',
        'CustomField1',
        'CustomField2',
        'CustomField3',
        'CustomField4',
        'CustomField5',
        'CustomField6',
        'CustomField7',
        'CustomField8',
        'CustomField9',
        'CustomField10',
        'CustomField11',
        'CustomField12',
        'CustomField13',
        'CustomField14',
        'MsgVer',
        'billingAddress',
        'shippingAddress',
        'billToCustomer',
        'shipToCustomer',
        'Card',
    ];

    protected $QHMACFields = ['PayID', 'TransID', 'MerchantID', 'Amount', 'Currency'];

    protected $RHMACFields = ['PayID', 'TransID', 'MerchantID', 'Status', 'Code'];

    protected $BFishFields = [
        'PayID',
        'TransID',
        'Amount',
        'Currency',
        'MAC',
        'RefNr',
        'Amount3D',
        'URLSuccess',
        'URLFailure',
        'URLNotify',
        'Response',
        'UserData',
        'Capture',
        'OrderDesc',
        'ReqID',
        'Plain',
        'Custom',
        'expirationTime',
        'AccVerify',
        'RTF',
        'ChDesc',
        'MID',
        'XID',
        'Status',
        'Description',
        'Code',
        'PCNr',
        'CCNr',
        'CCCVC',
        'CCBrand',
        'CCExpiry',
        'TermURL',
        'UserAgent',
        'HTTPAccept',
        'AboID',
        'ACSXID',
        'MaskedPan',
        'CAVV',
        'ECI',
        'DDD',
        'Type',
        'Plain',
        'Custom',
        'MsgVer',
        'billingAddress',
        'shippingAddress',
        'billToCustomer',
        'shipToCustomer',
        'Card',
    ];

    protected $requiredFields = ['MerchantID', 'TransID', 'Amount', 'Currency', 'OrderDesc'];

    protected $allowedLanguages = ['nl', 'fr', 'de', 'it', 'es', 'cy', 'en'];

    /**
     * Set the Merchant ID
     *
     * @param string $merchantId
     * @throws Exception
     */
    public function setMerchantId(string $merchantId)
    {
        $this->setParam('MerchantID', $merchantId);
    }

    /**
     * Set the HMAC
     *
     * @param string $secret
     */
    public function setSecretKey(string $secret)
    {
        $this->secretKey = $secret;
    }

    /**
     * Set the blowfish Crypt Key
     *
     * @param string $secret
     */
    public function setCryptKey(string $secret)
    {
        $this->cryptKey = $secret;
    }

    /**
     * Hack to retrieve response field
     *
     * @param string $encrypt
     * @throws Exception
     */
    public function setResponseParam(string $encrypt = 'encrypt')
    {
        $this->setParam('Response', $encrypt);
    }

    /**
     * Add bill to customer info
     *
     * @param array $billToCustomer
     *
     * @return void
     * @throws Exception
     */
    public function setBillToCustomer(array $billToCustomer = [])
    {
        $this->setParam('billToCustomer', $billToCustomer);
    }

    /**
     * Add ship to customer info
     *
     * @param array $shipToCustomer
     *
     * @return void
     * @throws Exception
     */
    public function setShipToCustomer(array $shipToCustomer = [])
    {
        $this->setParam('shipToCustomer', $shipToCustomer);
    }

    /**
     * Add billing address
     *
     * @param array $billingAddress
     * @throws Exception
     */
    public function setBillingAddress(array $billingAddress = [])
    {
        $this->setParam('billingAddress', $billingAddress);
    }

    /**
     * Add shipping address
     *
     * @param array $shippingAddress
     * @throws Exception
     */
    public function setShippingAddress(array $shippingAddress = [])
    {
        $this->setParam('shippingAddress', $shippingAddress);
    }

    /**
     * Set Msg Ver (default 3DSV2)
     *
     * @param string $version
     * @throws Exception
     */
    public function setMsgVer(string $version = '2.0')
    {
        $this->setParam('MsgVer', $version);
    }

    /**
     * Set card data
     *
     * @param string[] $card
     * @throws Exception
     */
    public function setCard(array $card = [])
    {
        $this->setParam('Card', $card);
    }

    /**
     * HMAC compute and store in MAC field
     *
     * @param array $parameters
     *
     * @return false|string
     * @throws Exception
     */
    public function shaCompose(array $parameters)
    {
        $shaString = '';

        foreach ($parameters as $key) {
            if (array_key_exists($key, $this->parameters) && !empty($this->parameters[$key])) {
                $shaString .= $this->parameters[$key];
            }
            $shaString .= (array_search($key, $parameters) != (count($parameters) - 1)) ? '*' : '';
        }

        $this->setParam('MAC', hash_hmac('sha256', $shaString, $this->secretKey));

        return $this->getParam('MAC');
    }

    /**
     * Get Sha Sign
     *
     * @return string
     * @throws Exception
     */
    public function getShaSign()
    {
        $this->validate();

        return $this->shaCompose($this->QHMACFields);
    }

    /**
     * BFish Compose
     *
     * @param array $parameters
     *
     * @return string
     * @throws Exception
     */
    public function BFishCompose(array $parameters): string
    {
        $blowfishString = '';

        foreach ($parameters as $key) {
            if (array_key_exists($key, $this->parameters) && !empty($this->parameters[$key])) {
                $blowfishString .= $key . '=' . $this->parameters[$key] . '&';
            }
        }

        $blowfishString = rtrim($blowfishString, '&');

        $this->setParam('Debug', $blowfishString);
        $this->setParam('Len', strlen($blowfishString));
        $this->setParam(self::DATA_FIELD, bin2hex($this->encrypt($blowfishString,$this->cryptKey)));

        return $this->getParam(self::DATA_FIELD);
    }

    /**
     * Retrieve length
     *
     * @return int
     * @throws Exception
     */
    public function getLen(): int
    {
        $length = $this->getParam('Len');
        if (!$length) {
            throw new Exception('The length is missing. Run BFishCompose first.');
        }

        return $this->getParam('Len');
    }

    /**
     * Retrieve BFish Crypt
     *
     * @return string
     * @throws Exception
     */
    public function getBFishCrypt(): string
    {
        $this->validate();

        return $this->BFishCompose($this->BFishFields);
    }

    /**
     * Encrypt
     *
     * @param string $data
     * @param string $key
     *
     * @return bool|string
     */
    private function encrypt(string $data, string $key)
    {
        $l = strlen($key);

        if ($l < 16) {
            $key = str_repeat($key, ceil(16 / $l));
        }

        if ($m = strlen($data)%8) {
            $data .= str_repeat("\x00", 8 - $m);
        }

        if (function_exists('mcrypt_encrypt')) {
            return mcrypt_encrypt(MCRYPT_BLOWFISH, $key, $data, MCRYPT_MODE_ECB);
        }

        return openssl_encrypt($data, 'BF-ECB', $key, OPENSSL_RAW_DATA | OPENSSL_NO_PADDING);
    }

    /**
     * Decrypt
     *
     * @param string $data
     * @param string $key
     *
     * @return string
     */
    private function decrypt(string $data, string $key): string
    {
        $l = strlen($key);

        if ($l < 16) {
            $key = str_repeat($key, ceil(16 / $l));
        }

        if (function_exists('mcrypt_encrypt')) {
            return rtrim(mcrypt_decrypt(MCRYPT_BLOWFISH, $key, $data, MCRYPT_MODE_ECB), "\0");
        }

        return rtrim(openssl_decrypt($data, 'BF-ECB', $key, OPENSSL_RAW_DATA | OPENSSL_NO_PADDING), "\0");
    }

    /**
     * Retrieve URL
     *
     * @return string
     */
    public function getUrl(): string
    {
        return $this->pspURL;
    }

    /**
     * Set URL
     *
     * @param string $pspUrl
     *
     * @return void
     * @throws Exception
     */
    public function setUrl(string $pspUrl)
    {
        $this->validateUri($pspUrl);

        $this->pspURL = $pspUrl;
    }

    /**
     * Set Success URL
     *
     * @param string $url
     *
     * @return void
     * @throws Exception
     */
    public function setURLSuccess(string $url)
    {
        $this->validateUri($url);
        $this->setParam('URLSuccess', $url);
    }

    /**
     * Set Failure URL
     *
     * @param string $url
     *
     * @return void
     * @throws Exception
     */
    public function setURLFailure(string $url)
    {
        $this->validateUri($url);
        $this->setParam('URLFailure', $url);
    }

    /**
     * Set URL Notify
     *
     * @param string $url
     * @throws Exception
     */
    public function setURLNotify(string $url)
    {
        $this->validateUri($url);
        $this->setParam('URLNotify', $url);
    }

    /**
     * Set URL Back
     *
     * @param string $url
     * @throws Exception
     */
    public function setURLBack(string $url)
    {
        $this->validateUri($url);
        $this->setParam('URLBack', $url);
    }

    /**
     * Set Transaction ID
     *
     * @param string $transactionReference
     *
     * @return void
     * @throws Exception
     */
    public function setTransID(string $transactionReference)
    {
        if(preg_match('/[^a-zA-Z0-9_-]/', $transactionReference)) {
            throw new Exception('TransactionReference cannot contain special characters');
        }

        $this->setParam('TransID', $transactionReference);
    }

    /**
     * Set amount in cents, e.g. EUR 12.34 is written as 1234
     *
     * @param int $amount
     *
     * @return void
     * @throws Exception
     */
    public function setAmount(int $amount)
    {
        if (!is_int($amount)) {
            throw new Exception('Integer expected. Amount is always in cents');
        }
        if ($amount <= 0) {
            throw new Exception('Amount must be a positive number');
        }

        $this->setParam('Amount', $amount);
    }

    /**
     * Set Capture day
     *
     * @param int $number
     *
     * @return void
     * @throws Exception
     */
    public function setCaptureDay(int $number)
    {
        if ($number > 9) {
            throw new Exception('captureDay is too long');
        }

        $this->setParam('captureDay', $number);
    }

    /**
     * Set Merchant's unique reference number
     *
     * @param mixed $refNr
     *
     * @return void
     * @throws Exception
     */
    public function setRefNr($refNr)
    {
        $this->setParam('RefNr', $refNr);
    }

    /**
     * Set language
     *
     * @param string $language
     *
     * @return void
     * @throws Exception
     */
    public function setLanguage(string $language)
    {
        $language = strtolower($language);
        if (!in_array($language, $this->allowedLanguages)) {
            $language = 'en';
        }

        $this->setParam('Language', $language);
    }

    /**
     * Set order description
     *
     * @param string $orderDesc
     *
     * @return void
     * @throws Exception
     */
    public function setOrderDesc(string $orderDesc)
    {
        $this->setParam('OrderDesc', $orderDesc);
    }

    /**
     * Set Capture day
     *
     * @param string $currency
     *
     * @return void
     * @throws Exception
     */
    public function setCurrency(string $currency)
    {
        if (strlen($currency) !== 3) {
            throw new Exception('Currency must be ISO 4217');
        }

        $this->setParam('Currency', $currency);
    }

    /**
     * Set Fraud Data Bypass 3DS
     *
     * @param string $value
     *
     * @return void
     * @throws Exception
     */
    public function setFraudDataBypass3DS(string $value)
    {
        if (strlen($value) > 128) {
            throw new Exception("fraudData.bypass3DS is too long");
        }

        $this->setParam('fraudData.bypass3DS', $value);
    }

    /**
     * Onclick
     *
     * @param string $wallet
     *
     * @return void
     * @throws Exception
     */
    public function setMerchantWalletId(string $wallet)
    {
        if (strlen($wallet) > 21) {
            throw new Exception('merchantWalletId is too long');
        }

        $this->setParam('merchantWalletId', $wallet);
    }

    /**
     * Validate parameters
     *
     * @return void
     * @throws Exception
     */
    public function validate()
    {
        foreach ($this->requiredFields as $field) {
            if (!empty($this->parameters[$field])) {
                continue;
            }

            throw new Exception($field . ' can not be empty');
        }
    }

    /**
     * Validate the URI
     *
     * @param string $uri
     *
     * @return void
     * @throws Exception
     */
    protected function validateUri(string $uri)
    {
        if (!filter_var($uri, FILTER_VALIDATE_URL)) {
            throw new Exception('Uri is not valid');
        }
        if (strlen($uri) > 200) {
            throw new Exception('Uri is too long');
        }
    }

    /**
     * Set Response
     *
     * @param array $httpRequest
     *
     * @return void
     */
    public function setResponse(array $httpRequest)
    {
        $this->parameters = $this->filterRequestParameters($httpRequest);
    }

    /**
     * Filter http request parameters
     *
     * @param array $httpRequest
     *
     * @return array
     */
    private function filterRequestParameters(array $httpRequest): array
    {
        $parameters = $this->parameters;

        if (!array_key_exists(self::DATA_FIELD, $httpRequest) || $httpRequest[self::DATA_FIELD] == '') {
            $parameters['Debug'] = implode('&', $httpRequest);
            foreach ($httpRequest as $key => $value) {
                $key = ($key === 'mid') ? 'MerchantID' : $key;
                $parameters[$key] = $value;
            }
        } else {
            $parameters[self::DATA_FIELD] = $httpRequest[self::DATA_FIELD];
            $dataString = $this->decrypt(hex2bin($parameters[self::DATA_FIELD]), $this->cryptKey);
            $parameters['Debug'] = $dataString;
            $dataParams = explode('&', $dataString);
            foreach($dataParams as $dataParamString) {
                $dataKeyValue = explode('=', $dataParamString, 2);
                $key = ($dataKeyValue[0] === 'mid') ? 'MerchantID' : $dataKeyValue[0];
                $parameters[$key] = $dataKeyValue[1];
            }
        }

        return $parameters;
    }

    /**
     * Checks if the response is valid
     *
     * @return bool
     * @throws Exception
     */
    public function isValid(): bool
    {
        return $this->shaCompose($this->RHMACFields) === $this->getParam('MAC');
    }

    /**
     * Is successful
     *
     * @return bool
     * @throws Exception
     */
    public function isSuccessful(): bool
    {
        return in_array($this->getParam('Status'), ['OK', 'AUTHORIZED']);
    }

    /**
     * Set parameter
     *
     * @param string $key
     * @param mixed $value
     *
     * @return void
     * @throws Exception
     */
    public function setParam(string $key, $value)
    {
        if (!in_array($key, $this->pspFields)) {
            throw new Exception('Parameter ' . $key . ' does not exist.');
        }

        $this->parameters[$key] = $value;
    }

    /**
     * Retrieve a parameter
     *
     * @param string $key
     *
     * @return mixed
     * @throws Exception
     */
    public function getParam(string $key)
    {
        $parameters = $this->parameters;

        if (!array_key_exists($key, $parameters)) {
            throw new Exception('Parameter ' . $key . ' does not exist.');
        }

        return $parameters[$key];
    }

    /**
     * Retrieve all parameters
     *
     * @return mixed[]
     */
    public function getParams(): array
    {
        return $this->parameters;
    }

    /**
     * Retrieve request to debug
     *
     * @return string
     */
    public function getDebug(): string
    {
        return $this->parameters['Debug'] ?? '';
    }
}
