<?php

namespace Maximethebault\INSAMiamAPI\Router;

use Maximethebault\INSAMiamAPI\Model\Dessert;
use Maximethebault\INSAMiamAPI\Model\Main;
use Maximethebault\INSAMiamAPI\Model\Meal;
use Maximethebault\INSAMiamAPI\Model\Starter;
use Maximethebault\IntraFetcher\Config;
use Slim\Slim;

$app = new Slim();
$app->response->headers->set('Content-Type', 'application/json');

$app->group('/api', function () use ($app) {
    $app->group('/meal', function () use ($app) {
        $app->get('/', function () use ($app) {
            // are the Mc Download's fans in the place tonight?!
            // let's get the api meal (lolilol)
            $mealOptions = array();
            $mealOptions['limit'] = 25;
            $mealOptions['include'] = array('textlines');
            $validated = $app->request()->get('validated');
            if($validated !== null) {
                $validated = (int) $validated;
                $mealOptions['conditions'] = array('validated=?', $validated);
            }
            echo '[' . implode(',', array_map(
                    function ($meal) {
                        return $meal->to_json(array('include' => array('textlines' => array('except' => array('starter_id', 'main_id', 'dessert_id'), 'include' => array('starter', 'main', 'dessert')))));
                    }, Meal::all($mealOptions))) . ']';
        });
        $app->post('/', function () use ($app) {
            $post = json_decode($app->request()->getBody());
            var_dump($post);
            $meal = Meal::find((int) $post->id);
            $meal->closed = (bool) $post->closed;
            $meal->validated = true;
            if($meal->closed) {
                $meal->save();
                return;
            }
            $courses = $post->meal;
            foreach($courses as $course) {
                if($course->type != 'starter' && $course->type != 'main' && $course->type != 'dessert') {
                    //invalid type
                    continue;
                }
                $objectName = 'Maximethebault\\INSAMiamAPI\\Model\\' . ucfirst($course->type);
                $linkObjectName = 'Maximethebault\\INSAMiamAPI\\Model\\Meal' . ucfirst($course->type);
                $linkAttrName = $course->type . '_id';
                if(!isset($course->id)) {
                    $courseObject = new $objectName();
                    $courseObject->name = $course->name;
                    $courseObject->save();
                }
                else {
                    $courseObject = $objectName::find((int) $course->id);
                }
                $linkObject = new $linkObjectName();
                $linkObject->meal_id = $meal->id;
                $linkObject->$linkAttrName = $courseObject->id;
                $linkObject->save();
            }
            $meal->save();
        });
        $app->get('/:id', function ($id) {
            echo Meal::find($id)->to_json();
        });
    });
    $app->group('/starter', function () use ($app) {
        $app->get('/', function () use ($app) {
            $similar = $app->request()->get('similar');
            echo '[' . implode(',', array_map(
                    function ($starter) {
                        return $starter->to_json();
                    }, Starter::all(array('select' => array('id, name, MATCH (name) AGAINST (?) AS score', '*' . $similar . '*')), array('order' => 'score desc'), array('conditions' => array("MATCH (name) AGAINST (?)", '*' . $similar . '*'))))) . ']';
        });
    });
    $app->group('/main', function () use ($app) {
        $app->get('/', function () use ($app) {
            $similar = $app->request()->get('similar');
            echo '[' . implode(',', array_map(
                    function ($main) {
                        return $main->to_json();
                    }, Main::all(array('select' => array('id, name, MATCH (name) AGAINST (?) AS score', '*' . $similar . '*')), array('order' => 'score desc'), array('conditions' => array("MATCH (name) AGAINST (?)", '*' . $similar . '*'))))) . ']';
        });
    });
    $app->group('/dessert', function () use ($app) {
        $app->get('/', function () use ($app) {
            $similar = $app->request()->get('similar');
            echo '[' . implode(',', array_map(
                    function ($dessert) {
                        return $dessert->to_json();
                    }, Dessert::all(array('select' => array('id, name, MATCH (name) AGAINST (?) AS score', '*' . $similar . '*')), array('order' => 'score desc'), array('conditions' => array("MATCH (name) AGAINST (?)", '*' . $similar . '*'))))) . ']';
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