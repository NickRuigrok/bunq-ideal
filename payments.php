<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

use bunq\Context\ApiContext;
use bunq\Util\BunqEnumApiEnvironmentType;
use bunq\Model\Generated\Endpoint\User;
use bunq\Model\Generated\Endpoint\UserPerson;
use bunq\Model\Generated\Endpoint\MonetaryAccount;
use bunq\Model\Generated\Object\Amount;
use bunq\Model\Generated\Object\Pointer;
use bunq\Model\Generated\Endpoint\BunqMeTab;
use bunq\Model\Generated\Endpoint\BunqMeTabEntry;
require_once('vendor/autoload.php');

require_once('database/database.php');

$database = new Database('database/bunqSession.db');

$requestAmount = $_GET['amount'];
$productId = $_GET['pid'];
$paymentDescription = 'order_id: ' . (time() * 1000) . ";\n product_id: $productId;";
const FILENAME_BUNQ_CONFIG = 'bunq.conf';
const INDEX_FIRST = 0;

/*
const deviceServerDescription = 'bunq-ideal';
const permitted_ips = [];
$apiKey = 'b4f3206b6de53e905829f0d14ef41c7a984a010a4452f9719af0a1a7ed3d960a';
$apiContext = ApiContext::create(BunqEnumApiEnvironmentType::PRODUCTION(), $apiKey, deviceServerDescription, permitted_ips);
$database->setBunqContext($apiContext->toJson());
*/

$apiContext = ApiContext::restore(FILENAME_BUNQ_CONFIG);
$database->setBunqContext($apiContext->toJson());

$apiContext = ApiContext::fromJson($database->getBunqContext());
$apiContext->ensureSessionActive();
$database->setBunqContext($apiContext->toJson());

$users = User::listing($apiContext)->getValue();

$user = $users[INDEX_FIRST]->getUserPerson();
$userId = $user->getId();
$monetaryAccounts = MonetaryAccount::listing($apiContext, $userId)->getValue();
$monetaryAccount = $monetaryAccounts[INDEX_FIRST]->getMonetaryAccountBank();
$monetaryAccountId = $monetaryAccount->getId();

$requestMap = [
    BunqMeTab::FIELD_BUNQME_TAB_ENTRY => [
        BunqMeTabEntry::FIELD_AMOUNT_INQUIRED => new Amount($requestAmount, 'EUR'),
        BunqMeTabEntry::FIELD_DESCRIPTION => $paymentDescription,
        BunqMeTabEntry::FIELD_REDIRECT_URL => "http://www.winkeldochter.nu/",
    ],
];

$createBunqMeTab = BunqMeTab::create($apiContext, $requestMap, $userId, $monetaryAccountId)->getValue();
$bunqMeRequest = BunqMeTab::get($apiContext, $userId, $monetaryAccountId, $createBunqMeTab)->getValue();

header('Content-Type: application/json');
echo json_encode($bunqMeRequest->getBunqmeTabShareUrl());