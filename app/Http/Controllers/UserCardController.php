<?php

namespace App\Http\Controllers;

use App\Models\Client;
use Illuminate\Http\Request;
use App\Models\UserCard;
use Throwable;
use DB;

class UserCardController extends Controller {
  public function index(Request $req) {
    try {
      $client = Client::find(Client::getclientIDByUserId($req->user()->id));
      $items = UserCard::getItems($req->user()->id);
      foreach ($items as $item) {
        $card = OpenpayController::getCard($client->customer_id, $item->card_id);
        $item->card_number = $card->card_number;
        $item->holder_name = $card->holder_name;
        $item->expiration_year = $card->expiration_year;
        $item->expiration_month = $card->expiration_month;
        $item->type = $card->type;
        $item->brand = $card->brand;
        $item->bank_name = $card->bank_name;
      }
      return $this->apiRsp(
        200,
        'Registros retornados correctamente',
        ['items' => $items]
      );
    } catch (Throwable $err) {
      return $this->apiRsp(500, null, $err);
    }
  }

  public function show(Request $req, $id) {
    try {
      $client = Client::find(Client::getclientIDByUserId($req->user()->id));
      $item = UserCard::find($id);
      $card = OpenpayController::getCard($client->customer_id, $item->card_id);
      $item->card_number = $card->card_number;
      $item->holder_name = $card->holder_name;
      $item->expiration_year = $card->expiration_year;
      $item->expiration_month = $card->expiration_month;
      $item->type = $card->type;
      $item->brand = $card->brand;
      $item->bank_name = $card->bank_name;
      return $this->apiRsp(
        200,
        'Registro retornado correctamente',
        ['item' => $item]
      );
    } catch (Throwable $err) {
      return $this->apiRsp(500, null, $err);
    }
  }

  public function destroy(Request $req, $id) {
    DB::beginTransaction();
    try {
      $item = UserCard::find($id);

      if (!$item) {
        return $this->apiRsp(422, 'ID no existente');
      }

      $client = Client::find(Client::getclientIDByUserId($req->user()->id));
      OpenpayController::deleteCard($client->customer_id, $item->card_id);

      $item->is_active = false;
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
      $item = UserCard::find($req->id);

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
        ['item' => UserCard::getItem(null, $item->id)]
      );
    } catch (Throwable $err) {
      DB::rollback();
      return $this->apiRsp(500, null, $err);
    }
  }


  public static function saveItem($item, $data) {
    $item->card_id = GenController::filter($data->card_id, 'U');

    $item->save();

    return $item;
  }

  public function saveCard(Request $req) {
    DB::beginTransaction();
    try {
      $valid = UserCard::valid($req->all());

      if ($valid->fails()) {
        return $this->apiRsp(422, $valid->errors()->first());
      }

      $client_id = Client::getclientIDByUserId($req->user()->id);
      $client = Client::find($client_id);

      if (!$client->customer_id) {
        $client->customer_id = ClientController::createCustomer($client_id);
      }

      $req->customer_id = $client->customer_id;

      $card_id = OpenpayController::createCard($req);

      $card = new UserCard;

      $card->user_id = $req->user()->id;
      $card->card_id = $card_id;
      $card->is_favorite = $req->is_favorite;
      $card->save();

      if ($card->is_favorite) {
        UserCard::setUnfavorite($req->user()->id);
      }


      DB::commit();
      return $this->apiRsp(
        200,
        'Registro creado correctamente',
        // ['item' => UserCard::getItem(null, $item->id)]
      );
    } catch (Throwable $err) {
      DB::rollback();
      return $this->apiRsp(500, null, $err);
    }
  }

  public function setFavorite(Request $req) {
    try {
      UserCard::setUnfavorite($req->user()->id);
      $user_card = UserCard::find($req->user_card_id);
      $user_card->is_favorite = true;
      $user_card->save();
      return $this->apiRsp(
        200,
        'Se ha registrado una targeta como favorita'
      );
    } catch (Throwable $err) {
      return $this->apiRsp(500, null, $err);
    }
  }
}