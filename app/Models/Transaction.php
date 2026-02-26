<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use DateTimeInterface;
use Carbon\Carbon;
use Validator;

class Transaction extends Model
{

  protected function serializeDate(DateTimeInterface $date)
  {
    return Carbon::instance($date)->toISOString(true);
  }
  protected $casts = [
    'created_at' => 'datetime:Y-m-d H:i:s',
    'updated_at' => 'datetime:Y-m-d H:i:s',
  ];

  public static function valid($data, $is_req = true)
  {
    $rules = [
      'status' => 'required|boolean',
      'card_number' => 'required|min:2|max:20',
      'bank_type_id' => 'required|numeric',
      'payment_form_id' => 'required|numeric',
      'authorization_code' => 'required|min:2|max:20',
      'reading_mode' => 'nullable|min:2|max:5',
      'arqc' => 'nullable|min:2|max:20',
      'aid' => 'nullable|min:2|max:20',
      'financial_reference' => 'nullable|min:2|max:20',
      'terminal_number' => 'nullable|min:2|max:10',
      'transaction_sequence' => 'nullable|min:2|max:20',
      'cardholder_name' => 'required|min:2|max:100',
      'error_message' => 'nullable|numeric',
      'response_code' => 'nullable|min:2|max:5',
      'is_points_used' => 'required|boolean',
      'points_redeemed' => 'nullable|numeric',
      'amount_redeemed' => 'nullable|numeric',
      'previous_balance_amount' => 'nullable|numeric',
      'previous_balance_points' => 'nullable|numeric',
      'current_balance_amount' => 'nullable|numeric',
      'current_balance_points' => 'nullable|numeric',
      'operation_date' => 'required|numeric',
      'external_payment_id' => 'nullable|min:2|max:25',
      'payment_id' => 'required|numeric',
    ];

    if (!$is_req) {
      array_push($rules, ['is_active' => 'required|in:true,false,1,0']);
    }

    $msgs = [];

    return Validator::make($data, $rules, $msgs);
  }

  static public function getUiid($id)
  {
    return 'T-' . str_pad($id, 4, '0', STR_PAD_LEFT);
  }

  static public function getItems($req)
  {
    $items = Transaction::where('is_active', (int) $req->is_active);

    $items = $items->get();

    foreach ($items as $key => $item) {
      $item->key = $key;
      $item->uiid = Transaction::getUiid($item->id);
    }

    return $items;
  }

  static public function getItem($req, $id)
  {
    $item = Transaction::find($id);

    if ($item) {
      $item->uiid = Transaction::getUiid($item->id);
      $item->created_by = User::find($item->created_by_id, ['email']);
      $item->updated_by = User::find($item->updated_by_id, ['email']);
    }

    return $item;
  }
}