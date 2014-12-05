<?php

namespace Maximethebault\INSAMiamAPI\Controller;

use Maximethebault\INSAMiamAPI\Model\Meal;
use Maximethebault\IntraFetcher\Config;
use Slim\Slim;

$app = new Slim();
$app->response->headers->set('Content-Type', 'application/json');

$app->group('/api', function () use ($app) {
    $app->group('/meal', function () use ($app) {
        $app->get('/', function () {
            // are the Mc Download's fans in the place tonight?!
            // let's get the api meal (lolilol)
            echo '[' . implode(',', array_map(
                    function ($meal) {
                        return $meal->to_json(array('include' => array('textlines')));
                    }, Meal::all(array('include' => array('textlines'))))) . ']';
        });
        $app->get('/:id', function ($id) {
            echo Meal::find($id)->to_json();
        });
    });
});
$app->get('/cron', function () use ($app) {
    $config = new Config();
    $config->setInsaUsername(\ActualConfig::$username);
    $config->setInsaPassword(\ActualConfig::$password);
    $config->setTempPath(__DIR__ . '/../../tmp/');
    $config->setPdfPath(__DIR__ . '/../../menus/');
    try {
        Meal::populateDb($config);
    }
    catch(\Exception $e) {
        mail(\ActualConfig::$adminEmail, 'INSA-Miam-Exception', get_class($e) . "\n" . $e->getMessage() . "\n" . $e->getTraceAsString());
        throw $e;
    }
});
$app->run();