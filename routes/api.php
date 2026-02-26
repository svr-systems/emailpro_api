<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\CatalogController;
use App\Http\Controllers\ClientController;
use App\Http\Controllers\DomainController;
use App\Http\Controllers\EmailDomainController;
use App\Http\Controllers\TicketController;
use App\Http\Controllers\UserCardController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\UserFiscalDataController;
use App\Http\Middleware\EnsureUserIsClient;
use Illuminate\Support\Facades\Route;

Route::post('login', [AuthController::class, 'login']);

Route::post('ticket/email', [TicketController::class, 'sendTicket']);

Route::group(['prefix' => 'public'], function () {
  Route::group(['prefix' => 'users'], function () {
    Route::group(['prefix' => 'password'], function () {
      Route::group(['prefix' => 'reset'], function () {
        Route::post('{id}', [UserController::class, 'passwordReset']);
        Route::get('{id}', [UserController::class, 'getItemPasswordReset']);
      });
      Route::post('recover', [UserController::class, 'passwordRecover']);
    });

    Route::group(['prefix' => 'account_confirm'], function () {
      Route::post('{id}', [UserController::class, 'accountConfirm']);
      Route::get('{id}', [UserController::class, 'getItemAccountConfirm']);
    });
  });
});

Route::group(['middleware' => 'auth:api'], function () {
  Route::post('logout', [AuthController::class, 'logout']);

  Route::get('/catalogs/{catalog}', [CatalogController::class, 'index']);

  Route::group(['prefix' => 'domains'], function () {
    Route::post('restore', [DomainController::class, 'restore']);

    Route::group(['prefix' => 'emails'], function () {
      Route::post('restore', [EmailDomainController::class, 'restore']);
    });
    Route::apiResource('emails', EmailDomainController::class);
  });
  Route::apiResource('domains', DomainController::class);

  Route::group(['prefix' => 'clients'], function () {
    Route::post('restore', [ClientController::class, 'restore']);
  });
  Route::apiResource('clients', ClientController::class);

  Route::group(['prefix' => 'users'], function () {
    Route::post('restore', [UserController::class, 'restore']);
  });
  Route::apiResource('users', UserController::class);
});

//CLIENTS
Route::group(['middleware' => 'auth:api'], function () {
  Route::middleware([EnsureUserIsClient::class])->group(function () {
    Route::group(['prefix' => 'client'], function () {

      //Domain list
      Route::get('domains', [DomainController::class, 'indexClient']);
      Route::get('domains/{id}', [DomainController::class, 'showClient']);
      //E-mail by domain
      Route::get('domains/{id}/emails', [EmailDomainController::class, 'indexClient']);

      //CARDS
      Route::group(['prefix' => 'cards'], function () {
        Route::get('/{user_card_id}', [UserCardController::class, 'show']);
        Route::delete('/{user_card_id}', [UserCardController::class, 'destroy']);
        Route::get('/', [UserCardController::class, 'index']);
        Route::post('/', [UserCardController::class, 'saveCard']);
        Route::post('/favorite', [UserCardController::class, 'setFavorite']);
      });

      //USER FISCAL DATA
      Route::get('user_fiscal_data', [UserFiscalDataController::class, 'getClientFiscalData']);
      Route::post('user_fiscal_data', [UserFiscalDataController::class, 'setClientFiscalData']);
    });
  });
});