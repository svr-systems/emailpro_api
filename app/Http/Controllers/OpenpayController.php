<?php

namespace App\Http\Controllers;

use App\Models\BankType;
use App\Models\Client;
use DB;
use Illuminate\Http\Request;
use Openpay\Data\Openpay;
use Throwable;

class OpenpayController extends Controller {
  static function createCustomer($user) {
    $openpay = Openpay::getInstance(
      env('OPENPAY_MERCHANT_ID'),
      env('OPENPAY_CUSTOMER_ID'),
      'MX',
      '127.0.0.1'
    );

    $customerData = array(
      // 'external_id' => $user->id,
      'name' => $user->name,
      'last_name' => trim($user->paternal_surname . ' ' . $user->maternal_surname),
      'email' => $user->email,
      'phone' => $user->phone,
      'requires_account' => false
    );

    $customer = $openpay->customers->add($customerData);

    return $customer->id;
  }
  static function editCustomer($user,$customer_id) {
    $openpay = Openpay::getInstance(
      env('OPENPAY_MERCHANT_ID'),
      env('OPENPAY_CUSTOMER_ID'),
      'MX',
      '127.0.0.1'
    );

    $customer = $openpay->customers->get($customer_id);
    $customer->name = $user->name;
    $customer->last_name = trim($user->paternal_surname . ' ' . $user->maternal_surname);
    $customer->email =  $user->email;
    $customer->phone =  $user->phone;
    $customer->save();
  }

  static function createCard($req) {
    $openpay = Openpay::getInstance(
      env('OPENPAY_MERCHANT_ID'),
      env('OPENPAY_CUSTOMER_ID'),
      'MX',
      '127.0.0.1'
    );

    $card_data_request = array(
      'holder_name' => $req->holder_name,
      'card_number' => $req->card_number,
      'cvv2' => $req->cvv2,
      'expiration_month' => $req->expiration_month,
      'expiration_year' => $req->expiration_year,
      // 'device_session_id' => 'kR1MiQhz2otdIuUlQkbEyitIqVMiI16f',
    );

    $customer = $openpay->customers->get($req->customer_id);
    $card = $customer->cards->add($card_data_request);

    return $card->id;
  }

  static function getCard($customer_id, $card_id) {
    $openpay = Openpay::getInstance(
      env('OPENPAY_MERCHANT_ID'),
      env('OPENPAY_CUSTOMER_ID'),
      'MX',
      '127.0.0.1'
    );

    $customer = $openpay->customers->get($customer_id);
    $card = $customer->cards->get($card_id);

    return $card;
  }

  static function deleteCard($customer_id, $card_id) {
    $openpay = Openpay::getInstance(
      env('OPENPAY_MERCHANT_ID'),
      env('OPENPAY_CUSTOMER_ID'),
      'MX',
      '127.0.0.1'
    );

    $customer = $openpay->customers->get($customer_id);
    $card = $customer->cards->get($card_id);
    $card->delete();
  }
}
