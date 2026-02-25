<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use DateTimeInterface;
use Carbon\Carbon;
use Validator;

class Email extends Model
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
      'email' => 'required|min:2|max:65',
      'domain_id' => 'required|numeric',
    ];

    if (!$is_req) {
      array_push($rules, ['is_active' => 'required|in:true,false,1,0']);
    }

    $msgs = [];

    return Validator::make($data, $rules, $msgs);
  }

  static public function getUiid($id)
  {
    return 'E-' . str_pad($id, 4, '0', STR_PAD_LEFT);
  }

  static public function getItems($req)
  {
    $items = Email::where('is_active', (int) $req->is_active)->
      where('domain_id',$req->domain_id);

    $items = $items->get();

    foreach ($items as $key => $item) {
      $item->key = $key;
      $item->uiid = Email::getUiid($item->id);
    }

    return $items;
  }

  static public function getItem($req, $id)
  {
    $item = Email::find($id);

    if ($item) {
      $item->uiid = Email::getUiid($item->id);
      $item->created_by = User::find($item->created_by_id, ['email']);
      $item->updated_by = User::find($item->updated_by_id, ['email']);
    }

    return $item;
  }
}