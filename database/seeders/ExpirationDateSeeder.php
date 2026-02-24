<?php

namespace Database\Seeders;

use App\Models\ExpirationDate;
use Illuminate\Database\Seeder;

class ExpirationDateSeeder extends Seeder {
  public function run() {
    $items = [
      [
        'name' => 'Un mes',
        'months' => 1
      ],
      [
        'name' => 'Tres meses',
        'months' => 3
      ],
      [
        'name' => 'Seis meses',
        'months' => 6
      ],
    ];

    ExpirationDate::insert($items);
  }
}
