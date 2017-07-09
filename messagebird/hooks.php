<?php

if (!defined("WHMCS")) {
  die("This file cannot be accessed directly");
}

use Illuminate\Database\Capsule\Manager as Capsule;

require_once(__DIR__ . '/autoload.php');

// setup messagebird
function setupMessageBird() {
  $apiKey = "";

  // get the api key from the database
  $accessKeyData = Capsule::table("tbladdonmodules")
    ->select("value AS api_key")
    ->where("setting", "=", "api_key")
    ->where("module", "=", "messagebird")
    ->first();

  if ($accessKeyData) {
    $apiKey = $accessKeyData->api_key;
  }

  // return messagebird object
  return new \MessageBird\Client($apiKey);
}

function lookupDialCode($countryCode) {
  // load the the dial codes from the json file
  $json = file_get_contents(__DIR__."/CountryCodes.json");
  $countries = json_decode($json);

  // go through each country and check if it's the one we want
  foreach ($countries as $country) {
    if ($country->code == $countryCode) {
      return $country->dial_code;
    }
  }
}

function sendSMS($content, $phonenumber) {
  // setup a new messagebird client
  $MessageBird = setupMessageBird();

  // make a new message to send
  $Message              = new \MessageBird\Objects\Message();
  $Message->originator  = "GG Gaming";
  $Message->recipients  = array($phonenumber);
  $Message->body        = $content;

  try {
    // try and send a text message
    $MessageResult = $MessageBird->messages->create($Message);
    //var_dump($MessageResult);
  } catch (\MessageBird\Exceptions\AuthenticateException $e) {
    echo "MessageBird Module Error: Invalid API Key!";
  } catch (\MessageBird\Exceptions\BalanceException $e) {
    // do nothing
  }
}

add_hook('AcceptOrder', 1, function($vars) {
  // get order data from database
  $orderData = Capsule::table("tblorders")
    ->where("id", "=", $vars['orderid'])
    ->first();

  // get the order number
  $orderNum = $orderData->ordernum;

  // get the user's id
  $userId =  $orderData->userid;

  // get the user's data
  $userData = Capsule::table("tblclients")
    ->where("id", "=", $userId)
    ->first();

  $firstName = $userData->firstname;
  $phoneNumber = lookupDialCode($userData->country).substr($userData->phonenumber, 1);

  // send a text message
  sendSMS("Hi ".$firstName.", Your recent order (#".$orderNum.") has been accepted!", $phoneNumber);
});

add_hook('ShoppingCartCheckoutCompletePage', 1, function($vars) {
  // get order data from database
  $orderData = Capsule::table("tblorders")
    ->where("id", "=", $vars["orderid"])
    ->first();

  $userId = $orderData->userid;

  //$userData = Capsule::table("tblclients")
  //  ->where("id", "=", $userId)
  //  ->first();

  $firstName = $vars["clientdetails"]["firstname"];
  $phoneNumber = lookupDialCode($vars["clientdetails"]["country"]).substr($vars["clientdetails"]["phonenumber"], 1);

  // send a text message
  sendSMS("Hi ".$firstName.", You just bought a new order (#".$vars["ordernumber"].") costing ".$vars["currency"]["prefix"].$vars["amount"].".", $phoneNumber);
});

add_hook('InvoiceCreated', 1, function($vars) {
  // get invoice data from database
  $invoiceData = Capsule::table("tblinvoices")
    ->where("id", "=", $vars["invoiceid"])
    ->first();

  $amount = $invoiceData->subtotal;

  // get the user's data
  $userData = Capsule::table("tblclients")
    ->where("id", "=", $vars["user"])
    ->first();

  $currencyId = $userData->currency;
  $firstName = $userData->firstname;
  $phoneNumber = lookupDialCode($userData->country).substr($userData->phonenumber, 1);

  // get currency data
  $currencyData = Capsule::table("tblcurrencies")
    ->where("id", "=", $currencyId)
    ->first();

  // send a text message
  sendSMS("Hi ".$firstName.", You have a new Invoice due for ".date("j/m/Y", strtotime($invoiceData->duedate))." costing ".$currencyData->prefix.$amount.".", $phoneNumber);
});
