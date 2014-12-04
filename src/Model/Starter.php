<?php

namespace Maximethebault\INSAMiamAPI\Model;

class Starter extends Course
{
    public static $table_name = 'starter';

    static $has_many = array(
        array('mealStarters', 'class_name' => 'MealStarter', 'foreign_key' => 'starter_id')
    );
} 