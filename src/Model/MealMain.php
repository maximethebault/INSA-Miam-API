<?php

namespace Maximethebault\INSAMiamAPI\Model;

class MealMain extends MealCourse
{
    public static $table_name = 'meal_main';

    static $belongs_to = array(
        array('meal'),
        array('main')
    );
} 