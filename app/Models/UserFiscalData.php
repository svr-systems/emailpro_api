<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use DateTimeInterface;
use Carbon\Carbon;
use Validator;

class UserFiscalData extends Model {

  protected function serializeDate(DateTimeInterface $date) {
    return Carbon::instance($date)->toISOString(true);
  }
  protected $casts = [
    'created_at' => 'datetime:Y-m-d H:i:s',
    'updated_at' => 'datetime:Y-m-d H:i:s',
  ];

  public static function valid($data, $is_req = true) {
    $rules = [
      // 'user_id' => 'required|numeric',
      'code' => 'required|min:2|max:13',
      'name' => 'required|min:2|max:75',
      'zip' => 'required|min:2|max:5',
      'fiscal_regime_id' => 'required|numeric',
      'cfdi_usage_id' => 'required|numeric',
    ];

    if (!$is_req) {
      array_push($rules, ['is_active' => 'required|in:true,false,1,0']);
    }

    $msgs = [];

    return Validator::make($data, $rules, $msgs);
  }

  static public function getUiid($id) {
    return 'UFD-' . str_pad($id, 4, '0', STR_PAD_LEFT);
  }

  static public function getItems($req) {
    $items = UserFiscalData::where('is_active', (int) $req->is_active);

    $items = $items->get();

    foreach ($items as $key => $item) {
      $item->key = $key;
      $item->uiid = UserFiscalData::getUiid($item->id);
      $item->fiscal_regime = FiscalRegime::find($item->fiscal_regime_id, ['name', 'code']);
      $item->cfdi_usage = CfdiUsage::find($item->cfdi_usage_id, ['name', 'code']);
    }

    return $items;
  }

  static public function getItem($user_id) {
    $item = UserFiscalData::where('user_id', $user_id)->
      first();

    if ($item) {
      $item->uiid = UserFiscalData::getUiid($item->id);
      $item->created_by = User::find($item->created_by_id, ['email']);
      $item->updated_by = User::find($item->updated_by_id, ['email']);
      $item->fiscal_regime = FiscalRegime::find($item->fiscal_regime_id, ['name', 'code']);
      $item->cfdi_usage = CfdiUsage::find($item->cfdi_usage_id, ['name', 'code']);
    } else {
      $item = new \stdClass;

      $item->id = '';
      $item->is_active = true;
      $item->code = '';
      $item->name = '';
      $item->zip = '';
      $item->uiid = '';

      $item->fiscal_regime_id = '';
      $item->fiscal_regime = new \stdClass;
      $item->fiscal_regime->id = '';
      $item->fiscal_regime->name = '';
      $item->fiscal_regime->code = '';

      $item->cfdi_usage_id = '';
      $item->cfdi_usage = new \stdClass;
      $item->cfdi_usage->id = '';
      $item->cfdi_usage->name = '';
      $item->cfdi_usage->code = '';
    }

    return $item;
  }

  static public function getClientItem($req, $user_id) {
    $item = UserFiscalData::where('user_id', $user_id)->
      first(['id','is_active','code','name','zip','fiscal_regime_id','cfdi_usage_id']);

    if ($item) {
      $item->uiid = UserFiscalData::getUiid($item->id);
      $item->fiscal_regime = FiscalRegime::find($item->fiscal_regime_id, ['name', 'code']);
      $item->cfdi_usage = CfdiUsage::find($item->cfdi_usage_id, ['name', 'code']);
    } else {
      $item = new \stdClass;

      $item->id = '';
      $item->is_active = true;
      $item->code = '';
      $item->name = '';
      $item->zip = '';
      $item->uiid = '';

      $item->fiscal_regime_id = '';
      $item->fiscal_regime = new \stdClass;
      $item->fiscal_regime->id = '';
      $item->fiscal_regime->name = '';
      $item->fiscal_regime->code = '';

      $item->cfdi_usage_id = '';
      $item->cfdi_usage = new \stdClass;
      $item->cfdi_usage->id = '';
      $item->cfdi_usage->name = '';
      $item->cfdi_usage->code = '';
    }

    return $item;
  }
}