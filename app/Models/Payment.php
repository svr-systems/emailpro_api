<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use DateTimeInterface;
use Carbon\Carbon;
use Validator;

class Payment extends Model {

  protected function serializeDate(DateTimeInterface $date) {
    return Carbon::instance($date)->toISOString(true);
  }
  protected $casts = [
    'created_at' => 'datetime:Y-m-d H:i:s',
    'updated_at' => 'datetime:Y-m-d H:i:s',
  ];

  public static function valid($data, $is_req = true) {
    $rules = [
      'client_id' => 'required|numeric',
      'service_id' => 'required|numeric',
      'amount' => 'required|numeric',
      'description' => 'required|min:2|max:100',
    ];

    if (!$is_req) {
      array_push($rules, ['is_active' => 'required|in:true,false,1,0']);
    }

    $msgs = [];

    return Validator::make($data, $rules, $msgs);
  }

  static public function getUiid($id) {
    return 'P-' . str_pad($id, 4, '0', STR_PAD_LEFT);
  }

  static public function getItems($req) {
    $items = Payment::where('is_active', (int) $req->is_active)->
      where('client_id', $req->client_id);

    $items = $items->get();

    foreach ($items as $key => $item) {
      $item->key = $key;
      $item->uiid = Payment::getUiid($item->id);
    }

    return $items;
  }

  static public function getItem($req, $id) {
    $item = Payment::find($id);

    if ($item) {
      $item->uiid = Payment::getUiid($item->id);
      $item->created_by = User::find($item->created_by_id, ['email']);
      $item->updated_by = User::find($item->updated_by_id, ['email']);
    }

    return $item;
  }

  static public function getPaymentsByClientNumber($client_number) {
    $items = Payment::join('clients', 'clients.id', 'payments.client_id')->
      where('client_number', $client_number)->
      where('payments.is_active', true)->
      where('is_paid', false);

    $items = $items->get(['payments.id as payment_id', 'amount', 'description']);

    foreach ($items as $key => $item) {
      $item->key = $key;
      $item->uiid = Payment::getUiid($item->payment_id);
    }

    return $items;
  }

  static public function getEmailData($id) {
    $item = Payment::find($id, ['id', 'created_at', 'client_id', 'service_id', 'amount', 'description']);

    if ($item) {
      $item->uiid = Payment::getUiid($item->id);
      $item->service = Domain::find($item->domain, ['name']);
    }

    return $item;
  }
}