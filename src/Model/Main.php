<?php

namespace Maximethebault\INSAMiamAPI\Model;

class Main extends Course
{
    public static $table_name = 'main';

    static $has_many = array(
        array('mealMains', 'class_name' => 'MealMain', 'foreign_key' => 'main_id')
    );

    public static $has_one = array(
        array('main', 'class_name' => 'Main', 'foreign_key' => 'main_id')
    );
} 