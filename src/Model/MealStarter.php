<?php

namespace Maximethebault\INSAMiamAPI\Model;

class MealStarter extends MealCourse
{
    public static $table_name = 'meal_starter';

    static $belongs_to = array(
        array('meal'),
        array('starter')
    );
} 