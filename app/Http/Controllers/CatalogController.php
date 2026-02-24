<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Throwable;

class CatalogController extends Controller {
  public function index(Request $req, $catalog) {
    try {
      $model = match ($catalog) {
        'roles' => \App\Models\Role::class,
        default => null,
      };

      abort_if(!$model, 404, 'Catálogo no encontrado');
      return $this->apiRsp(
        200,
        'Registros retornados correctamente',
        ['items' => $model::getItems($req)]
      );
    } catch (Throwable $err) {
      return $this->apiRsp(500, null, $err);
    }
  }
  
  public function publicCatalog(Request $req, $catalog) {
    try {
      $model = match ($catalog) {
        default => null,
      };

      abort_if(!$model, 404, 'Catálogo no encontrado');
      return $this->apiRsp(
        200,
        'Registros retornados correctamente',
        ['items' => $model::getItems($req)]
      );
    } catch (Throwable $err) {
      return $this->apiRsp(500, null, $err);
    }
  }
}
