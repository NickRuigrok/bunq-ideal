<?php
use bunq\Context\ApiContext;
use bunq\Http\Pagination;
use bunq\Model\Generated\Endpoint\BunqMeTab;
use bunq\Model\Generated\Endpoint\MonetaryAccount;
use bunq\Model\Generated\Endpoint\Payment;
use bunq\Model\Generated\Endpoint\User;

require_once 'vendor/autoload.php';

/*
 * Very first index in an array.
 */
const INDEX_FIRST = 0;
/*
 * Size of each page of payments listing.
 */
const PAGE_SIZE = 4;

$apiContext = ApiContext::restore(ApiContext::FILENAME_CONFIG_DEFAULT);
$users = User::listing($apiContext)->getValue();
$apiContext->save();
$userContainer = $users[INDEX_FIRST];

if (!is_null($userContainer->getUserLight())) {
    $user = $userContainer->getUserLight();
} elseif (!is_null($userContainer->getUserPerson())) {
    $user = $userContainer->getUserPerson();
} else {
    $user = $userContainer->getUserCompany();
}

$userId = $user->getId();

$monetaryAccounts = MonetaryAccount::listing($apiContext, $userId)->getValue();
$monetaryAccount = $monetaryAccounts[INDEX_FIRST]->getMonetaryAccountBank();
$monetaryAccountId = $monetaryAccount->getId();

$paginationCountOnly = new Pagination();
$paginationCountOnly->setCount(PAGE_SIZE);

$paymentsResponse = Payment::listing(
    $apiContext,
    $userId,
    $monetaryAccountId,
    $paginationCountOnly->getUrlParamsCountOnly()
);

$payments = $paymentsResponse->getValue();
$payments = json_encode($payments);
$payments = json_decode($payments);

foreach ($payments as $payment) {
    $paymentDesc = $payment->description;
    $paymentDesc = explode(";", $paymentDesc);
    $counterIBAN = $payment->counterparty_alias->iban;
    $counterName = $payment->counterparty_alias->display_name;
    echo $paymentDesc[0] . " - " . $counterIBAN . " [" . $counterName . "] <br/>";
}