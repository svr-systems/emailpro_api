<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Client;
use App\Models\User;
use Throwable;
use DB;

class ClientController extends Controller {
  public function index(Request $req) {
    try {
      return $this->apiRsp(
        200,
        'Registros retornados correctamente',
        ['items' => Client::getItems($req)]
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
        ['item' => Client::getItem($req, $id)]
      );
    } catch (Throwable $err) {
      return $this->apiRsp(500, null, $err);
    }
  }

  public function destroy(Request $req, $id) {
    DB::beginTransaction();
    try {
      $item = Client::find($id);

      if (!$item) {
        return $this->apiRsp(422, 'ID no existente');
      }

      $user = User::find($item->user_id);
      $user->is_active = false;
      $user->updated_by_id = $req->user()->id;
      $user->save();

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
      $item = Client::find($req->id);

      if (!$item) {
        return $this->apiRsp(422, 'ID no existente');
      }

      $user = User::find($item->user_id);
      $user->is_active = true;
      $user->updated_by_id = $req->user()->id;
      $user->save();

      DB::commit();
      return $this->apiRsp(
        200,
        'Registro activado correctamente',
        ['item' => Client::getItem(null, $item->id)]
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
      $store_mode = is_null($id);
      $item = $this->saveItem($req, $id);

      if ($item['msg']) {
        return $this->apiRsp(422, $item['msg']);
      }

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

  public function saveItem($req, $id) {
    // $user = json_encode($req->user);
    // $user_data = json_decode($user);
    $user_data = json_decode($req->user);
    $user_data->role_id = 2;
    $email = GenController::filter($user_data->email, 'l');

    $valid = User::validEmail(['email' => $email], $user_data->id);
    if ($valid->fails()) {
      return ['msg' => $valid->errors()->first()];
    }

    $valid = User::valid((array) $user_data);
    if ($valid->fails()) {
      return ['msg' => $valid->errors()->first()];
    }
    $store_mode = is_null($id);

    if ($store_mode) {
      $user = new User;
      $user->created_by_id = $req->user()->id;
      $user->updated_by_id = $req->user()->id;

      $item = new Client;
      $openpay = new OpenpayController;
      $customer_id = $openpay->createCustomer($user_data);
    } else {
      $item = Client::find($id);
      $user = User::find($item->user_id);

      $user->updated_by_id = $req->user()->id;
      $customer_id = $item->customer_id;
      $openpay = new OpenpayController;
      $openpay->editCustomer($user_data, $customer_id);
    }

    $user = UserController::saveItem($user, $user_data);

    $item->user_id = $user->id;
    $item->customer_id = $customer_id;

    if (!$user->email_verified_at) {
      EmailController::userAccountConfirmation($user->email, $user);
    }

    $item->save();

    return $item;
  }

  public function search(Request $req) {
    try {
      return $this->apiRsp(
        200,
        'Registros retornados correctamente',
        ['item' => Client::search($req)]
      );
    } catch (Throwable $err) {
      return $this->apiRsp(500, null, $err);
    }
  }
}