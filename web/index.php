<?php


require_once __DIR__.'/../vendor/autoload.php';

$app = new Silex\Application();
$app['debug'] = true;
$app->register(new Silex\Provider\ServiceControllerServiceProvider());
//$app->register(new Silex\Provider\VarDumperServiceProvider());

$app['http.client'] = function() {
    return new GuzzleHttp\Client();
};

$app['csv.service'] = function() use ($app) {
    return new \CSVAPI\Service($app['http.client']);
};
$app['csv.controller'] = function() use ($app) {
    return new \CSVAPI\Controllers\ServiceController($app, $app['csv.service']);
};

$app->get('/data', "csv.controller:parseDataAction");

$app->run();