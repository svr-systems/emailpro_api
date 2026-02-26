<?php

namespace App\Http\Controllers;

use App\Models\BankType;
use App\Models\Payment;
use Illuminate\Http\Request;
use App\Models\Transaction;
use Throwable;
use DB;

class TransactionController extends Controller {

  public function saveTransaction($req) {
    DB::beginTransaction();
    try {
      $valid = Transaction::valid($req->all());

      if ($valid->fails()) {
        return $this->apiRsp(422, $valid->errors()->first());
      }

      $item = new Transaction;
      $item->created_by_id = $req->user()->id;
      $item->updated_by_id = $req->user()->id;

      $bank_type = BankType::getByCode($req->bank_code);
      $payment_form_id = ($req->card_product === 'c') ? 4 : 18;
      $req->bank_type_id = $bank_type->id;
      $req->payment_form_id = $payment_form_id;

      $item = $this->saveItem($item, $req);

      DB::commit();
      return $this->apiRsp(
        200,
        'Pagp agregado correctamente'
      );
    } catch (Throwable $err) {
      DB::rollback();
      return $this->apiRsp(500, null, $err);
    }
  }

  public static function saveItem($item, $data) {
    $item->status = GenController::filter($data->status, 'b');
    $item->card_number = GenController::filter($data->card_number, 'U');
    $item->bank_type_id = GenController::filter($data->bank_type_id, 'id');
    $item->payment_form_id = GenController::filter($data->payment_form_id, 'id');
    $item->authorization_code = GenController::filter($data->authorization_code, 'U');
    $item->reading_mode = GenController::filter($data->reading_mode, 'U');
    $item->arqc = GenController::filter($data->arqc, 'U');
    $item->aid = GenController::filter($data->aid, 'U');
    $item->financial_reference = GenController::filter($data->financial_reference, 'U');
    $item->terminal_number = GenController::filter($data->terminal_number, 'U');
    $item->transaction_sequence = GenController::filter($data->transaction_sequence, 'U');
    $item->cardholder_name = GenController::filter($data->cardholder_name, 'U');
    $item->error_message = (isset($data->error_message)) ? GenController::filter($data->error_message, 'd') : null;
    $item->response_code = (isset($data->response_code)) ? GenController::filter($data->response_code, 'U') : null;
    $item->is_points_used = (isset($data->is_points_used)) ? GenController::filter($data->is_points_used, 'b') : null;
    $item->points_redeemed = (isset($data->points_redeemed)) ? GenController::filter($data->points_redeemed, 'f') : null;
    $item->amount_redeemed = (isset($data->amount_redeemed)) ? GenController::filter($data->amount_redeemed, 'f') : null;
    $item->previous_balance_amount = (isset($data->previous_balance_amount)) ? GenController::filter($data->previous_balance_amount, 'f') : null;
    $item->previous_balance_points = (isset($data->previous_balance_points)) ? GenController::filter($data->previous_balance_points, 'f') : null;
    $item->current_balance_amount = (isset($data->current_balance_amount)) ? GenController::filter($data->current_balance_amount, 'f') : null;
    $item->current_balance_points = (isset($data->current_balance_points)) ? GenController::filter($data->current_balance_points, 'f') : null;
    $item->operation_date = (isset($data->operation_date)) ? GenController::filter($data->operation_date, 'd') : null;
    $item->external_payment_id = (isset($data->external_payment_id)) ? GenController::filter($data->external_payment_id, 'U') : null;

    $item->save();

    return $item;
  }
}