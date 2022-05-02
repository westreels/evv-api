<?php

use Dingo\Api\Routing\Router;
use Illuminate\Http\Request;
use App\Models\Gamelist;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

/*
 * Welcome route - link to any public API documentation here
 */
Route::get('/', function () {
    echo 'API';
});

//Route::any('/callback/hollyjolly/endpoint', 'App\Http\Controllers\GameControllers\HollywoodController@endpoint')->name('HollywoodController');


/** @var \Dingo\Api\Routing\Router $api */
$api = app('Dingo\Api\Routing\Router');
$api->version('v1', ['middleware' => ['api']], function (Router $api) {

    $api->group(['prefix' => 'api'], function (Router $api) {
            //$api->any('/callback/inbetgames', 'App\Http\Controllers\GameControllers\VirtualSportsController@virtualCallbacks');
            //$api->any('/callback/inbet', 'App\Http\Controllers\GameControllers\VirtualSportsController@virtualCallbacks');
    });

    $api->group(['prefix' => 'callbackNew'], function (Router $api) {
            $api->any('/evolutiongaming567/endpoint/balance', 'App\Http\Controllers\GameControllers\EvolutionGamingController@balance');
            $api->any('/evolutiongaming567/endpoint/bet', 'App\Http\Controllers\GameControllers\EvolutionGamingController@result');
    });


    $api->group(['prefix' => 'evconnect'], function (Router $api) {
            $api->any('/startSession', 'App\Http\Controllers\GameControllers\SessionController@createSession');
            $api->any('/createSession', 'App\Http\Controllers\GameControllers\SessionController@createSession');
            //$api->any('/listGame', 'App\Http\Controllers\GetGamesController@gameList');
            //$api->any('/listGames', 'App\Http\Controllers\GetGamesController@gameList');
            //$api->any('/recentGamesPlayedBot', 'App\Http\Controllers\GetGamesController@getRecentCount');
    });


    $api->group(['prefix' => 'session'], function (Router $api) {
            $api->any('/OMGreal/evolutiongaming/{playerId}/{gameId}/{casino_id}/{mode}/{name}/{lang}', 'App\Http\Controllers\GameControllers\EvolutionGamingController@createGame');
    });


    /*
     * Authenticated routes
    $api->group(['middleware' => ['api.auth']], function (Router $api) { 
        /*
         * Authentication
        $api->group(['prefix' => 'auth'], function (Router $api) {
            $api->group(['prefix' => 'jwt'], function (Router $api) {
                $api->get('/refresh', 'App\Http\Controllers\Auth\AuthController@refresh');
                $api->delete('/token', 'App\Http\Controllers\Auth\AuthController@logout');
            });

            $api->get('/me', 'App\Http\Controllers\Auth\AuthController@getUser');
        });

        /*
         * Users
        $api->group(['prefix' => 'users', 'middleware' => 'check_role:admin'], function (Router $api) {
            $api->get('/', 'App\Http\Controllers\UserController@getAll');
            $api->get('/{uuid}', 'App\Http\Controllers\UserController@get');
            $api->post('/', 'App\Http\Controllers\UserController@post');
            $api->put('/{uuid}', 'App\Http\Controllers\UserController@put');
            $api->patch('/{uuid}', 'App\Http\Controllers\UserController@patch');
            $api->delete('/{uuid}', 'App\Http\Controllers\UserController@delete');
        });

        /*
         * Roles
        $api->group(['prefix' => 'roles'], function (Router $api) {
            $api->get('/', 'App\Http\Controllers\RoleController@getAll');
        });
    });         */

});
