<?php

namespace Maximethebault\INSAMiamAPI\Model;

class MealDessert extends MealCourse
{
    public static $table_name = 'meal_dessert';

    static $belongs_to = array(
        array('meal'),
        array('dessert')
    );
} 