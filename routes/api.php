<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\CatalogController;
use App\Http\Controllers\ClientController;
use App\Http\Controllers\DomainController;
use App\Http\Controllers\TicketController;
use App\Http\Controllers\UserController;
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