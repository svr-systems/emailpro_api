<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class role extends Model
{
  public $timestamps = false;

  static public function getItems($req) {
    $items = Role::
      orderBy('name')->
      where('is_active', true);

    $items = $items->get([
      'id',
      'name',
    ]);

    return $items;
  }
}
