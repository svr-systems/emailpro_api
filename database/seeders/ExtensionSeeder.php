<?php

namespace Database\Seeders;

use App\Models\Extension;
use Illuminate\Database\Seeder;

class ExtensionSeeder extends Seeder {
  public function run() {
    $items = [
      [
        'name' => 'com'
      ],
      [
        'name' => 'mx'
      ],
    ];

    Extension::insert($items);
  }
}
