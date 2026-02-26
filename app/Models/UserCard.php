<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use DateTimeInterface;
use Carbon\Carbon;
use Validator;

class UserCard extends Model {

  protected function serializeDate(DateTimeInterface $date) {
    return Carbon::instance($date)->toISOString(true);
  }
  protected $casts = [
    'created_at' => 'datetime:Y-m-d H:i:s',
    'updated_at' => 'datetime:Y-m-d H:i:s',
  ];

  public static function valid($data, $is_req = true) {
    $rules = [
      'card_number' => 'required|min:16|max:19',
      'holder_name' => 'required|min:2|max:50',
      'cvv2' => 'required|numeric',
      'expiration_month' => 'required|min:2|max:2',
      'expiration_year' => 'required|min:2|max:2',
    ];

    if (!$is_req) {
      array_push($rules, ['is_active' => 'required|in:true,false,1,0']);
    }

    $msgs = [];

    return Validator::make($data, $rules, $msgs);
  }

  static public function getUiid($id) {
    return 'UC-' . str_pad($id, 4, '0', STR_PAD_LEFT);
  }

  static public function getItems($user_id) {
    $items = UserCard::where('user_id', $user_id)->
      where('is_active', true)->
      orderBy('is_favorite', 'DESC');

    $items = $items->get(['id', 'card_id', 'is_favorite']);

    foreach ($items as $key => $item) {
      $item->key = $key;
      $item->uiid = UserCard::getUiid($item->id);
    }

    return $items;
  }

  static public function getItem($req, $id) {
    $item = UserCard::find($id, [['id', 'card_id', 'is_favorite']]);

    if ($item) {
      $item->uiid = UserCard::getUiid($item->id);
    }

    return $item;
  }

  static public function setUnfavorite($user_id) {
    $item = UserCard::where('user_id', $user_id)->
      update(['is_favorite' => false]);
  }

  static public function setfavoriteByCard_id($card_id) {
    $item = UserCard::where('card_id', $card_id)->
      update(['is_favorite' => true]);
  }
}