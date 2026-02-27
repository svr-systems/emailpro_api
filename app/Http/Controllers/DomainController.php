<?php

namespace App\Http\Controllers;

use App\Models\Client;
use Illuminate\Http\Request;
use App\Models\Domain;
use Throwable;
use DB;

class DomainController extends Controller {
  public function index(Request $req) {
    try {
      return $this->apiRsp(
        200,
        'Registros retornados correctamente',
        ['items' => Domain::getItems($req)]
      );
    } catch (Throwable $err) {
      return $this->apiRsp(500, null, $err);
    }
  }

  public function show(Request $req, $id) {
    try {
      return $this->apiRsp(
        200,
        'Registro retornado correctamente',
        ['item' => Domain::getItem($req, $id)]
      );
    } catch (Throwable $err) {
      return $this->apiRsp(500, null, $err);
    }
  }

  public function destroy(Request $req, $id) {
    DB::beginTransaction();
    try {
      $item = Domain::find($id);

      if (!$item) {
        return $this->apiRsp(422, 'ID no existente');
      }

      $item->is_active = false;
      $item->updated_by_id = $req->user()->id;
      $item->save();

      DB::commit();
      return $this->apiRsp(
        200,
        'Registro inactivado correctamente'
      );
    } catch (Throwable $err) {
      DB::rollback();
      return $this->apiRsp(500, null, $err);
    }

  }

  public function restore(Request $req) {
    DB::beginTransaction();
    try {
      $item = Domain::find($req->id);

      if (!$item) {
        return $this->apiRsp(422, 'ID no existente');
      }

      $item->is_active = true;
      $item->updated_by_id = $req->user()->id;
      $item->save();

      DB::commit();
      return $this->apiRsp(
        200,
        'Registro activado correctamente',
        ['item' => Domain::getItem(null, $item->id)]
      );
    } catch (Throwable $err) {
      DB::rollback();
      return $this->apiRsp(500, null, $err);
    }
  }

  public function store(Request $req) {
    return $this->storeUpdate($req, null);
  }

  public function update(Request $req, $id) {
    return $this->storeUpdate($req, $id);
  }

  public function storeUpdate($req, $id) {
    DB::beginTransaction();
    try {
      $valid = Domain::valid($req->all());

      if ($valid->fails()) {
        return $this->apiRsp(422, $valid->errors()->first());
      }

      $store_mode = is_null($id);

      if ($store_mode) {
        $item = new Domain;
        $item->created_by_id = $req->user()->id;
        $item->updated_by_id = $req->user()->id;
      } else {
        $item = Domain::find($id);
        $item->updated_by_id = $req->user()->id;
      }

      $item = $this->saveItem($item, $req);

      DB::commit();
      return $this->apiRsp(
        $store_mode ? 201 : 200,
        'Registro ' . ($store_mode ? 'agregado' : 'editado') . ' correctamente',
        $store_mode ? ['item' => ['id' => $item->id]] : null
      );
    } catch (Throwable $err) {
      DB::rollback();
      return $this->apiRsp(500, null, $err);
    }
  }

  public static function saveItem($item, $data) {
    $item->client_id = GenController::filter($data->client_id, 'id');
    $item->company = GenController::filter($data->company, 'U');
    $item->name = GenController::filter($data->name, 'l');
    $item->email_price = GenController::filter($data->email_price, 'd');
    $item->extention_id = GenController::filter($data->extention_id, 'id');
    $item->expire_at = GenController::filter($data->expire_at, 'd');
    $item->email_accounts = GenController::filter($data->email_accounts, 'd');

    $item->save();

    return $item;
  }

  ///////////////////////CLIENT//////////////////////////
  public function indexClient(Request $req) {
    try {
      return $this->apiRsp(
        200,
        'Registros retornados correctamente',
        ['items' => Domain::getClientItems(Client::getclientIDByUserId($req->user()->id))]
      );
    } catch (Throwable $err) {
      return $this->apiRsp(500, null, $err);
    }
  }
  public function showClient(Request $req,$id) {
    try {
      return $this->apiRsp(
        200,
        'Registros retornados correctamente',
        ['items' => Domain::getClientItem(Client::getclientIDByUserId($req->user()->id),$id)]
      );
    } catch (Throwable $err) {
      return $this->apiRsp(500, null, $err);
    }
  }
}