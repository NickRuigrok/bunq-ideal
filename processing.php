<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once 'vendor/autoload.php';
$fileName = 'bunq.conf';
use bunq\Context\ApiContext;
if (!file_exists('bunq.conf')){
    require_once 'vendor/init.php';
    $apiContext = ApiContext::restore($fileName);
}else{
    $apiContext = ApiContext::restore($fileName);
}
$apiContext->save($fileName);

function guid(){
    if (function_exists('com_create_guid')){
        return com_create_guid();
    }else{
        mt_srand((double)microtime()*10000);//optional for php 4.2.0 and up.
        $charid = strtoupper(md5(uniqid(rand(), true)));
        $hyphen = chr(45);// "-"
        $uuid = chr(123)// "{"
            .substr($charid, 0, 8).$hyphen
            .substr($charid, 8, 4).$hyphen
            .substr($charid,12, 4).$hyphen
            .substr($charid,16, 4).$hyphen
            .substr($charid,20,12)
            .chr(125);// "}"
        return $uuid;
    }
}

$ch = curl_init('http://winkeldochter.nu/bunq-ideal/payments.php?pid=' . $_POST['pid'] .'&amount=' . $_POST['amount']);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$result = curl_exec($ch);
curl_close($ch);

$uuid = substr(strrchr(json_decode($result, true), '/'), 1);

$data = array("amount_requested" => array("currency" => "EUR", "value" => $_POST['amount']), "bunqme_type" => "TAB", "issuer" => $_POST['issuer'], "merchant_type" => "IDEAL", "bunqme_uuid" => $uuid);
$data_string = json_encode($data);

$ch = curl_init('https://api.bunq.me/v1/bunqme-merchant-request');
curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
curl_setopt($ch, CURLOPT_POSTFIELDS, $data_string);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, array(
        'X-Bunq-Client-Request-Id: ' . guid())
);
$result = curl_exec($ch);
curl_close($ch);

$bunqMeResponse = json_decode($result, true);
$merchantUuid = $bunqMeResponse['Response'][0]['BunqMeMerchantRequest']['uuid'];
$paymentStatus = $bunqMeResponse['Response'][0]['BunqMeMerchantRequest']['status'];

sleep(2);

$ch = curl_init('https://api.bunq.me/v1/bunqme-merchant-request/' . $merchantUuid);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, array(
        'X-Bunq-Client-Request-Id: ' . guid())
);
$result = curl_exec($ch);
curl_close($ch);

$bunqMeResponse = json_decode($result, true);
$paymentStatus = $bunqMeResponse['Response'][0]['BunqMeMerchantRequest']['status'];
$paymentLink = $bunqMeResponse['Response'][0]['BunqMeMerchantRequest']['issuer_authentication_url'];
$paymentLink = str_replace("\\", "", $paymentLink);

if ($paymentStatus == 'PAYMENT_CREATED'){
    header("Location: " . $paymentLink);
}