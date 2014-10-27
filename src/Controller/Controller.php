<?php

namespace Maximethebault\INSAMiamAPI\Controller;

use Maximethebault\INSAMiamAPI\Model\Meal;

class Controller
{
    public function run() {
        $uris = explode('?', substr($_SERVER['REQUEST_URI'], 1));
        $query_path = explode('/', $uris[0]);
        while(array_shift($query_path) !== 'api' && count($query_path)) {
            ;
        }
        if(count($query_path)) {
            header('Content-type: application/json');
            $values = json_decode(file_get_contents('php://input'), true);
            $endpoint = array_shift($query_path);
            if($endpoint == 'meal') {
                $this->meal($query_path, $values);
            }
            else {
                throw new \Exception('Unknown endpoint');
            }
        }
        else {
            throw new \Exception('No endpoint');
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
                throw new \Exception('Unsupported HTTP verb for this endpoint');
        }
    }
} 