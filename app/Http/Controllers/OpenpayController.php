<?php

namespace App\Http\Controllers;

use App\Models\BankType;
use App\Models\Client;
use App\Models\Domain;
use App\Models\ExpirationDate;
use App\Models\Payment;
use App\Models\Transaction;
use App\Models\UserCard;
use DB;
use Illuminate\Http\Request;
use Openpay\Data\Openpay;
use Throwable;
use Carbon\Carbon;

class OpenpayController extends Controller {
  public function paymentUserCard(Request $req) {
    DB::beginTransaction();
    try {

      $client = Client::find(Client::getclientIDByUserId($req->user()->id));


      $openpay = Openpay::getInstance(
        env('OPENPAY_MERCHANT_ID'),
        env('OPENPAY_CUSTOMER_ID'),
        'MX',
        '127.0.0.1'
      );

      $customer = $openpay->customers->get($client->customer_id);

      $chargeData = array(
        'method' => 'card',
        'source_id' => $req->token_id,
        'amount' => $req->total,
        'description' => $req->domain_id . '-' . $req->expiration_date_id,
        'use_card_points' => $req->use_card_points,
        'device_session_id' => $req->device_session_id
      );

      $use_3d_secure = false;

      if (GenController::filter(env('OPENPAY_3D_SECURE_AMOUNT'), 'i')) {
        if ($req->total > GenController::filter(env('OPENPAY_3D_SECURE_AMOUNT'), 'f')) {
          $chargeData['use_3d_secure'] = true;
          $chargeData['redirect_url'] = 'https://apipagoselectronicos.svr.com.mx/pago_exitoso';
          $use_3d_secure = true;
        }
      }

      $charge = $customer->charges->create($chargeData);

      UserCard::setUnfavorite($req->user()->id);
      UserCard::setfavoriteByCard_id($req->token_id);

      $response = new \stdClass;

      if (!$use_3d_secure) {
        $response = $this->saveOpenpayTransaction($charge->id);
      } else {
        $response->redirect_url = $charge->payment_method->url;
      }

      DB::commit();
      return $response;

    } catch (Throwable $e) {
      $error_code = null;
      $description = null;
      try {
        $error_code = $e->getErrorCode();
        $description = $e->getMessage();
      } catch (Throwable $err) {
        return $this->apiRsp(500, null, $e);
      }
      $http_code = 500;
      $message = 'Transacción fallida. Comuniquese con su banco e ingrese sus datos correctamente e inténtelo de nuevo.';

      if ($error_code === 3004) {
        $message = 'Tarjeta declinada.';
        $http_code = 422;
      } elseif ($error_code === 3005) {
        $message = 'Tarjeta declinada.';
        $http_code = 422;
      } else {
        return $this->apiRsp(500, null, $e);
      }
      return $this->apiRsp(
        $http_code,
        $message,
        null
      );

    }
  }

  public function saveOpenpayTransaction($openpay_id) {
    DB::beginTransaction();
    try {
      $openpay = Openpay::getInstance(
        env('OPENPAY_MERCHANT_ID'),
        env('OPENPAY_CUSTOMER_ID'),
        'MX',
        '127.0.0.1'
      );

      $charge = $openpay->charges->get($openpay_id);

      $bank_type = BankType::getByCode($charge->card->bank_code);
      $payment_form_id = ($charge->card->type === 'debit') ? 18 : 4;
      $operation_date = date('Y-m-d H:i:s', strtotime($charge->operation_date));
      $status = ($charge->status === "completed") ? true : false;

      $transaction_data = new \stdClass;
      $transaction_data->status = $status;
      $transaction_data->card_number = str_replace('X', '*', $charge->card->card_number);
      $transaction_data->bank_type_id = $bank_type->id;
      $transaction_data->payment_form_id = $payment_form_id;
      $transaction_data->authorization_code = $charge->authorization;
      $transaction_data->reading_mode = null;
      $transaction_data->arqc = null;
      $transaction_data->aid = null;
      $transaction_data->financial_reference = null;
      $transaction_data->terminal_number = null;
      $transaction_data->transaction_sequence = null;
      $transaction_data->cardholder_name = $charge->card->holder_name;
      $transaction_data->error_message = $charge->error_message;
      $transaction_data->response_code = null;
      $transaction_data->is_points_used = false;
      $transaction_data->points_redeemed = null;
      $transaction_data->amount_redeemed = null;
      $transaction_data->previous_balance_amount = null;
      $transaction_data->previous_balance_points = null;
      $transaction_data->current_balance_amount = null;
      $transaction_data->current_balance_points = null;
      $transaction_data->operation_date = $operation_date;
      $transaction_data->payment_id = $openpay_id;
      $transaction_data->charge_amount = $charge->amount;

      $transaction = new Transaction;
      $transaction = TransactionController::saveItem($transaction, $transaction_data);


      if ($status) {
        $payment = new Payment;

        $domain_id = GenController::filter(explode('-', $charge->description)[0], 'id');
        $expiration_date_id = GenController::filter(explode('-', $charge->description)[1], 'id');

        $payment->domain_id = $domain_id;
        $payment->amount = $charge->amount;
        $payment->transaction_id = $transaction->id;
        $payment->expiration_date_id = $expiration_date_id;

        $payment->save();

        $expiration_date = ExpirationDate::find($expiration_date_id);
        $domain = Domain::find($domain_id);

        $today = Carbon::today();
        // Fecha base
        $base_date = (!$domain->expire_at || Carbon::parse($domain->expire_at)->lte($today))
          ? $today
          : Carbon::parse($domain->expire_at);

        // Nueva fecha
        $domain->expire_at = $base_date->copy()->addDays($expiration_date->days);

        $domain->save();
      }

      DB::commit();
      return $this->apiRsp(
        200,
        'Transacción terminada correctamente',
        ['status' => $status]
      );
    } catch (Throwable $err) {
      DB::rollback();
      return $this->apiRsp(500, null, $err);
    }
  }
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
  static function editCustomer($user, $customer_id) {
    $openpay = Openpay::getInstance(
      env('OPENPAY_MERCHANT_ID'),
      env('OPENPAY_CUSTOMER_ID'),
      'MX',
      '127.0.0.1'
    );

    $customer = $openpay->customers->get($customer_id);
    $customer->name = $user->name;
    $customer->last_name = trim($user->paternal_surname . ' ' . $user->maternal_surname);
    $customer->email = $user->email;
    $customer->phone = $user->phone;
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
