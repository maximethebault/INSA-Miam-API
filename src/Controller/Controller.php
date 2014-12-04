<?php

namespace Maximethebault\INSAMiamAPI\Controller;

use Maximethebault\INSAMiamAPI\Exception\APIException;
use Maximethebault\INSAMiamAPI\Model\Meal;
use Maximethebault\IntraFetcher\Config;

class Controller
{
    public function run() {
        $uris = explode('?', substr($_SERVER['REQUEST_URI'], 1));
        $query_path = explode('/', $uris[0]);
        $firstPart = array_shift($query_path);
        while(($firstPart !== 'api' && $firstPart != 'cron') && count($query_path)) {
            $firstPart = array_shift($query_path);
        }
        if($firstPart === 'api') {
            if(!count($query_path)) {
                throw new APIException('No endpoint');
            }
            header('Content-type: application/json');
            $values = json_decode(file_get_contents('php://input'), true);
            $endpoint = array_shift($query_path);
            if($endpoint == 'meal') {
                // are the Mc Download's fans in the place tonight?!
                // let's get the api meal (lolilol)
                $this->meal($query_path, $values);
            }
            else {
                throw new APIException('Unknown endpoint');
            }
        }
        elseif($firstPart === 'cron') {
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
        }
        else {
            throw new \Exception('Unknown action');
        }
    }

    public function meal(&$query_path) {
        switch($_SERVER['REQUEST_METHOD']) {
            case 'GET':
                $mealId = intval(array_shift($query_path));
                $exportOptions = array();
                if($mealId) {
                    echo Meal::find($mealId)->to_json($exportOptions);
                }
                else {
                    echo '[' . implode(',', array_map(
                            function ($meal) use ($exportOptions) {
                                return $meal->to_json($exportOptions);
                            }, Meal::all())) . ']';
                }
                break;
            default:
                throw new APIException('Unsupported HTTP verb for this endpoint');
        }
    }
} 