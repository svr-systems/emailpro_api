<?php

namespace App\Http\Controllers;

use App\Models\Client;
use Illuminate\Http\Request;
use App\Models\UserFiscalData;
use Throwable;
use DB;

class UserFiscalDataController extends Controller {

  public function getClientFiscalData(Request $req) {
    try {
      return $this->apiRsp(
        200,
        'Registro retornado correctamente',
        ['item' => UserFiscalData::getClientItem(null, $req->user()->id)]
      );
    } catch (Throwable $err) {
      return $this->apiRsp(500, null, $err);
    }
  }

  public function setClientFiscalData(Request $req) {
    DB::beginTransaction();
    try {
      $valid = UserFiscalData::valid($req->all());
      if ($valid->fails()) {
        return $this->apiRsp(422, $valid->errors()->first());
      }

      $valid = FacturapiDataController::validCustomer($req);
      if ($valid->msg !== null) {
        return $this->apiRsp(422, $valid->msg);
      }

      $user_id = $req->user()->id;
      $user_fiscal_data = UserFiscalData::getItem($user_id);
      $req->user_id = $user_id;

      $id = GenController::filter($user_fiscal_data->id, 'id');

      $store_mode = is_null($id);

      if ($store_mode) {
        $item = new UserFiscalData;
        $item->created_by_id = $req->user()->id;
      } else {
        $item = UserFiscalData::find($id);
      }

      $item->updated_by_id = $req->user()->id;

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
    $item->user_id = GenController::filter($data->user_id, 'id');
    $item->code = GenController::filter($data->code, 'U');
    $item->name = GenController::filter($data->name, 'U');
    $item->zip = GenController::filter($data->zip, 'U');
    $item->fiscal_regime_id = GenController::filter($data->fiscal_regime_id, 'id');
    $item->cfdi_usage_id = GenController::filter($data->cfdi_usage_id, 'id');

    $item->save();

    return $item;
  }
}