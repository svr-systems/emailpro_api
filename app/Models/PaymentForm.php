<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PaymentForm extends Model {
  use HasFactory;
  public $timestamps = false;

  static public function getItems($req) {
    $items = PaymentForm::
      orderBy('name')->
      where('is_active', true)->
      get();

    return $items;
  }
}