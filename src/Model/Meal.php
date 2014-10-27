<?php

namespace Maximethebault\INSAMiamAPI\Model;

use ActiveRecord\Model;
use Maximethebault\IntraFetcher\Config;
use Maximethebault\IntraFetcher\IntraFetcher;

class Meal extends Model
{
    public static $table_name = 'meal';

    public static function populateDb() {
        $config = new Config();
        $intraFetcher = new IntraFetcher($config);
        $intraFetcher->checkForMenu();
        foreach($intraFetcher->getNewMenu() as $menu) {
            $initialDateMonth = $menu->getMenuId()->
            $menu->getMenuTable()->getCell(1, 0);
        }
    }
}