<?php

namespace Maximethebault\INSAMiamAPI\Model;

class Dessert extends Course
{
    public static $table_name = 'dessert';

    static $has_many = array(
        array('mealDesserts', 'class_name' => 'MealDessert', 'foreign_key' => 'dessert_id')
    );
} 