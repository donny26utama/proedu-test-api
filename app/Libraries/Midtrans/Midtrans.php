<?php

namespace App\Libraries\Midtrans;

use Exception;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Client;

/**
 * Midtrans Payment Gateway class.
 * This class
 */
class Midtrans
{
    const SANDBOX_BASE_URL = 'https://api.sandbox.midtrans.com/v2';
    const PRODUCTION_BASE_URL = 'https://api.midtrans.com/v2';
    const SNAP_SANDBOX_BASE_URL = 'https://app.sandbox.midtrans.com/snap/v1/transactions';
    const SNAP_PRODUCTION_BASE_URL = 'https://app.midtrans.com/snap/v1/transactions';

    /**
     * Your merchant's server key
     *
     * @static
     */
    public static $serverKey;
    /**
     * Your merchant's client key
     *
     * @static
     */
    public static $clientKey;
    /**
     * True for production
     * false for sandbox mode
     *
     * @static
     */
    public static $isProduction = false;
    /**
     * Set it true to enable 3D Secure by default
     *
     * @static
     */
    public static $is3ds = false;
    /**
     *  Set Append URL notification
     *
     * @static
     */
    public static $appendNotifUrl;
    /**
     *  Set Override URL notification
     *
     * @static
     */
    public static $overrideNotifUrl;
    /**
     * Enable request params sanitizer (validate and modify charge request params).
     * See Midtrans_Sanitizer for more details
     *
     * @static
     */
    public static $isSanitized = false;
    /**
     * Default options for every request
     *
     * @static
     */
    public static $curlOptions = [];

    /**
     * Initialize Midtrans
     *
     * @param array $configs
     */
    public function __construct($configs = [])
    {
        foreach ($configs as $config => $value) {
            if (property_exists(__CLASS__, $config)) {
                self::$$config = $value;
            } elseif ($config === 'env') {
                self::$isProduction = $value === 'production';
            }
        }
    }

    /**
     * Create Snap payment page
     *
     * @param array $params Payment options
     * @return string Snap token.
     * @throws Exception curl error or midtrans error
     */
    public static function getSnapToken($params)
    {
        $endpoint = self::snapChargeUrl();
        return self::clientRequest($endpoint, 'POST', $params)->token;
    }

    /**
     * Create Snap payment page, with this version returning full API response
     *
     * @param array $params Payment options
     * @return object Snap response (token and redirect_url).
     * @throws Exception curl error or midtrans error
     */
    public static function createTransaction($params)
    {
        $endpoint = self::snapChargeUrl();
        return self::clientRequest($endpoint, 'POST', $params);
    }

    /**
     * Create transaction.
     *
     * @param mixed[] $params Transaction options
     */
    public static function charge($params)
    {
        
        $endpoint = self::baseUrl() . '/charge';
        return self::clientRequest($endpoint, 'POST', $params);
    }

    /**
     * charge alias, but returns redirect url attribute
     *
     * @param array $payloads
     */
    public static function vtWebCharge($params)
    {
        $endpoint = self::baseUrl() . '/charge';
        return self::clientRequest($endpoint, 'POST', $params)->redirect_url;
    }

    /**
     * Charge alias
     *
     * @param array $params
     */
    public static function vtDirectCharge($params)
    {
        $endpoint = self::baseUrl() . '/charge';
        return self::clientRequest($endpoint, 'POST', $params);
    }

    /**
     * Capture pre-authorized transaction
     *
     * @param string $param Order ID or transaction ID, that you want to capture
     */
    public function capture($params)
    {
        $endpoint = self::baseUrl() . '/capture';
        return self::clientRequest($endpoint, 'POST', $params);
    }

    /**
     * Retrieve transaction status
     *
     * @param string $id Order ID or transaction ID
     * @return mixed[]
     */
    public static function status($id)
    {
        $endpoint = self::baseUrl() . '/' . $id . '/status';
        return self::clientRequest($endpoint, 'GET');
    }

    /**
     * Approve challenge transaction
     *
     * @param string $id Order ID or transaction ID
     * @return string
     */
    public static function approve($id)
    {
        $endpoint = self::baseUrl() . '/' . $id . '/approve';
        return self::clientRequest($endpoint, 'POST')->status_code;
    }

    /**
     * Cancel transaction before it's settled
     *
     * @param string $id Order ID or transaction ID
     * @return string
     */
    public static function cancel($id)
    {
        $endpoint = self::baseUrl() . '/' . $id . '/cancel';
        return self::clientRequest($endpoint, 'POST')->status_code;
    }

    /**
     * Expire transaction before it's setteled
     *
     * @param string $id Order ID or transaction ID
     * @return mixed[]
     */
    public static function expire($id)
    {
        $endpoint = self::baseUrl() . '/' . $id . '/expire';
        return self::clientRequest($endpoint, 'POST');
    }

    /**
     * Transaction status can be updated into refund
     * if the customer decides to cancel completed/settlement payment.
     * The same refund id cannot be reused again.
     *
     * @param string $id Order ID or transaction ID
     * @return mixed[]
     */
    public static function refund($id, $params)
    {
        $endpoint = self::baseUrl() . '/' . $id . '/refund';
        return self::clientRequest($endpoint, 'POST', $params);
    }

    /**
     * Transaction status can be updated into refund
     * if the customer decides to cancel completed/settlement payment.
     * The same refund id cannot be reused again.
     *
     * @param string $id Order ID or transaction ID
     * @return mixed[]
     */
    public static function refundDirect($id, $params)
    {
        $endpoint = self::baseUrl() . '/' . $id . '/refund/online/direct';
        return self::clientRequest($endpoint, 'POST', $params);
    }

    /**
     * Deny method can be triggered to immediately deny card payment transaction
     * in which fraud_status is challenge.
     *
     * @param string $id Order ID or transaction ID
     * @return mixed[]
     */
    public static function deny($id)
    {
        $endpoint = self::baseUrl() . '/' . $id . '/deny';
        return self::clientRequest($endpoint, 'POST');
    }

    /**
     * Get baseUrl
     *
     * @return string Midtrans API URL, depends on $isProduction
     */
    private static function baseUrl()
    {
        return (self::$isProduction) ? self::PRODUCTION_BASE_URL : self::SANDBOX_BASE_URL;
    }

    /**
     * Get snapChargeUrl
     *
     * @return string Snap API URL, depends on $isProduction
     */
    private static function snapChargeUrl()
    {
        return (self::$isProduction) ? self::SNAP_PRODUCTION_BASE_URL : self::SNAP_SANDBOX_BASE_URL;
    }

    /**
     * Create and send an HTTP request
     *
     * @param string $url API URL
     * @param string $type HTTP Request method type
     * @param array $data Body request parameters
     * @return mixed
     */
    private static function clientRequest($url, $type, $data = null)
    {
        try {
            $client = new Client();
            $request = $client->request($type, $url, [
                'headers' => [
                    'Content-Type' => 'application/json',
                    'Accept' => 'application/json',
                    'Authorization' => 'Basic ' . base64_encode(self::$serverKey . ':'),
                ],
                'verify' => dirname(__FILE__) . '/cert/cacert.pem',
                'json' => $data,
            ]);

            return json_decode((string) $request->getBody(), true);
        } catch (ClientException $e) {
            throw new Exception ($e->getMessage() ,$e->getResponse()->getStatusCode());
        }
    }
}