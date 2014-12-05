<?php

namespace Maximethebault\INSAMiamAPI\Model;

use ActiveRecord\Model;

/**
 * @property int meal_id
 * @property float ordering
 * @property float char_size
 * @property float cell_size
 * @property string content
 */
class Textline extends Model
{
    public static $table_name = 'textline';
}