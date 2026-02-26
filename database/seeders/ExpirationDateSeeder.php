<?php

namespace Database\Seeders;

use App\Models\ExpirationDate;
use Illuminate\Database\Seeder;

class ExpirationDateSeeder extends Seeder {
  public function run() {
    $items = [
      [
        'name' => '30 días (Aprox. 1 mes)',
        'days' => 30
      ],
      [
        'name' => '90 días (Aprox. 3 meses)',
        'days' => 90
      ],
      [
        'name' => '180 días (Aprox. 6 meses)',
        'days' => 180
      ],
    ];

    ExpirationDate::insert($items);
  }
}
