<?php

/** @var \Laravel\Lumen\Routing\Router $router */

/*
|--------------------------------------------------------------------------
| Application Routes
|--------------------------------------------------------------------------
|
| Here is where you can register all of the routes for an application.
| It is a breeze. Simply tell Lumen the URIs it should respond to
| and give it the Closure to call when that URI is requested.
|
*/

$router->get('/', function () use ($router) {
    return $router->app->version();
});

$router->group(['prefix' => 'auth'], function() use ($router) {
    $router->post('/register', 'AuthController@register');
    $router->post('/login', 'AuthController@login');
});

$router->group(['prefix' => 'v1'], function() use ($router) {
    $router->get('/', function () use ($router) {
        return $router->app->version();
    });

    $router->group(['prefix' => 'seminars'], function() use ($router) {
        $router->get('/', 'SeminarController@index');
        $router->get('/{id}', 'SeminarController@show');
    });

    $router->group(['prefix' => 'transaction'], function() use ($router) {
        $router->post('/order', 'TransactionController@order');
        $router->post('/payment', 'TransactionController@payment');
        $router->get('/{id}/status', 'TransactionController@status');
        $router->post('/notification', 'TransactionController@notification');
    });
});
