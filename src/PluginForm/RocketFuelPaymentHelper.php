<?php


namespace Drupal\commerce_rocketfuel\PluginForm;


use Curl;

trait RocketFuelPaymentHelper
{
    protected function getMerchantAuth($merchant_id)
    {
        $out = "";
        $cert = dirname(__FILE__, 3) . '/key/.rf_public.key';
        if (!file_exists($cert)) {
            return false;
        }
        $cert = file_get_contents($cert);
        $to_crypt = $merchant_id;

        $public_key = openssl_pkey_get_public($cert);

        $key_lenght = openssl_pkey_get_details($public_key);

        $part_len = $key_lenght['bits'] / 8 - 11;

        $parts = str_split($to_crypt, $part_len);

        foreach ($parts as $part) {

            $encrypted_temp = '';

            openssl_public_encrypt($part, $encrypted_temp, $public_key, OPENSSL_PKCS1_OAEP_PADDING);

            $out .=  $encrypted_temp;
        }

        return base64_encode($out);
    }

    protected function getEndpoint($environment)
    {
        $environmentData = [
            'prod' => 'https://app.rocketfuelblockchain.com/api',
            'dev' => 'https://dev-app.rocketdemo.net/api',
            'stage2' => 'https://qa-app.rocketdemo.net/api',
            'preprod' => 'https://preprod-app.rocketdemo.net/api',
        ];

        return isset($environmentData[$environment]) ? $environmentData[$environment] : 'https://qa-app.rocketdemo.net/api';
    }

    public function getUUID($data)
    {
        $curl = new Curl();
        file_put_contents(__DIR__.'/log.json',json_encode($data),FILE_APPEND);
        $paymentResponse = $curl->processDataToRkfl($data);
        file_put_contents(__DIR__.'/response.json',json_encode($paymentResponse),FILE_APPEND);

        unset($curl);

        if (!$paymentResponse) {
            return false;
        }



        $result = $paymentResponse;

        if (!isset($result->result) && !isset($result->result->url)) {
            // wc_add_notice(__('Failed to place order', 'rocketfuel-payment-gateway'), 'error');
            return array('succcess' => 'false');
        }
        $urlArr = explode('/', $result->result->url);

        return $urlArr[count($urlArr) - 1];
    }
}