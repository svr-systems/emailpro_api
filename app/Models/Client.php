<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Http\Controllers\GenController;
use Illuminate\Database\Eloquent\Model;

class Client extends Model {
  use HasFactory;
  public $timestamps = false;

  static public function getUiid($id) {
    return 'C-' . str_pad($id, 4, '0', STR_PAD_LEFT);
  }

  static public function getItems($req) {
    $items = Client::
      join('users', 'clients.user_id', 'users.id')->
      where('users.is_active', boolval($req->is_active));

    $items = $items->
      get([
        'clients.id',
        'users.is_active',
        'user_id',
        'customer_id'
      ]);

    foreach ($items as $key => $item) {
      $item->key = $key;
      $item->uiid = Client::getUiid($item->id);
      $item->user = User::find($item->user_id);
      $item->user->full_name = GenController::getFullName($item->user);
    }

    return $items;
  }

  static public function getItem($req, $id) {
    $item = Client::
      find($id, [
        'id',
        'user_id',
        'customer_id'
      ]);

    if ($item) {
      $item->uiid = Client::getUiid($item->id);
      $item->user = User::getItem(null, $item->user_id);
      $item->user->full_name = GenController::getFullName($item->user);
    }

    return $item;
  }

  static public function getclientIDByUserId($user_id) {
    $item = Client::where('user_id', $user_id)->
      first([
        'id'
      ]);

    return $item->id;
  }
}