<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ExpirationDate extends Model {
  use HasFactory;
  public $timestamps = false;

  static public function getItems($req) {
    $items = ExpirationDate::
      orderBy('days')->
      where('is_active', true)->
      get();

    return $items;
  }
}