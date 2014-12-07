<?php

namespace Maximethebault\INSAMiamAPI\Model;

use ActiveRecord\DateTime;
use ActiveRecord\Model;
use Maximethebault\IntraFetcher\IntraFetcher;

/**
 * @property int                      id
 * @property \ActiveRecord\DateTime   date
 * @property string                   type
 * @property bool                     closed
 * @property bool                     validated
 */
class Meal extends Model
{
    const MEAL_TYPE_LUNCH = 'L';
    const MEAL_TYPE_DINNER = 'D';

    public static $table_name = 'meal';

    public static $has_many = array(
        array('textlines', 'order' => 'ordering asc', 'class_name' => 'Textline', 'foreign_key' => 'meal_id'),
        array('mealStarters', 'class_name' => 'MealStarter', 'foreign_key' => 'meal_id'),
        array('starters', 'class_name' => 'Starter', 'through' => 'mealStarters'),
        array('mealMains', 'class_name' => 'MealMain', 'foreign_key' => 'meal_id'),
        array('mains', 'class_name' => 'Main', 'through' => 'mealMains'),
        array('mealDesserts', 'class_name' => 'MealDessert', 'foreign_key' => 'meal_id'),
        array('desserts', 'class_name' => 'Dessert', 'through' => 'mealDesserts')
    );

    public static function populateDb($config) {
        $intraFetcher = new IntraFetcher($config);
        $intraFetcher->checkForMenu();
        // TODO: hash each part of the menu to know what was changed in case of update
        // TODO: manage menu where restaurant was closed (e.g., if we haven't had any menu for 8 weeks and now we do, fills those 8 weeks with closed = true)
        // TODO: better parsing, fuzzy search, ...
        // TODO: manage course splitted on several lines (take advantage of the uppercase to make the right choice!) (if a line is fully encapsuled with parenthesis, it probably goes with the previous line!)
        $newMenus = $intraFetcher->getNewMenu();
        if($newMenus) {
            foreach($intraFetcher->getNewMenu() as $menu) {
                $table = $menu->getMenuTable();
                $initialDate = new DateTime();
                $initialDate->setISODate($menu->getMenuId()->getYear(), $menu->getMenuId()->getWeekNumber());
                for($i = 1; $i <= 7; $i++) {

                    self::parseLunch($initialDate, $table->getCell($i, 1)->getTextline(), $table->getCell($i, 2)->getTextline());

                    self::parseDinner($initialDate, $table->getCell($i, 3)->getTextline());

                    $initialDate->modify('+1 day');
                }
            }
        }
        $updatedMenus = $intraFetcher->getUpdatedMenu();
        if($updatedMenus) {
            throw new \Exception("Menu updates are not supported yet!");
        }
        $intraFetcher->commitChanges();
    }

    /**
     * @param $date     DateTime
     * @param $starters \Maximethebault\Pdf2Table\XmlElements\Textline[]
     * @param $rest     \Maximethebault\Pdf2Table\XmlElements\Textline[]
     */
    public static function parseLunch($date, $starters, $rest) {
        // for the moment, naive parsing: assume one course per line, or 2 when separated with "/"
        $mealObject = new Meal();
        $mealObject->date = $date;
        $mealObject->type = self::MEAL_TYPE_LUNCH;
        $mealObject->validated = false;

        if(count($starters) <= 2 && count($rest) <= 2) {
            // restaurant is probably closed
            $mealObject->closed = true;
            if(!count($starters) && !count($rest)) {
                $mealObject->validated = true;
            }
            $mealObject->save();
            if(!count($starters) && !count($rest)) {
                return;
            }
        }
        else {
            $mealObject->closed = false;
            $mealObject->save();
        }
        // we saved the mealObject to be able to get the 'id'

        // we need to split the '$rest' in mains and desserts
        // if we can't find the typical separator, we default to no-dessert
        $splitIdx = count($rest);
        foreach($rest as $idx => $course) {
            if(preg_match('`yaourt`i', $course->getText())) {
                $splitIdx = $idx;
                break;
            }
        }
        $mains = array_slice($rest, 0, $splitIdx);
        $desserts = array_slice($rest, $splitIdx);

        self::parseMeal($mealObject, array($starters, $mains, $desserts));
    }

    /**
     * @param $date    DateTime
     * @param $courses \Maximethebault\Pdf2Table\XmlElements\Textline[]
     */
    public static function parseDinner($date, $courses) {
        // for the moment, naive parsing: assume one course per line, or 2 when separated with "/"
        $mealObject = new Meal();
        $mealObject->date = $date;
        $mealObject->type = self::MEAL_TYPE_DINNER;
        $mealObject->validated = false;

        if(count($courses) <= 2) {
            // restaurant is probably closed
            $mealObject->closed = true;
            if(!count($courses)) {
                $mealObject->validated = true;
            }
            $mealObject->save();
            if(!count($courses)) {
                return;
            }
        }
        else {
            $mealObject->closed = false;
            $mealObject->save();
        }
        // we saved the mealObject to be able to get the 'id'

        // we need to split the '$courses' in starters, mains and desserts
        // the starters only occupies 1 line (usually)
        $mainIdx = 1;
        $dessertIdx = count($courses);
        foreach($courses as $idx => $course) {
            if(preg_match('`yaourt`i', $course->getText())) {
                $dessertIdx = $idx;
                break;
            }
        }
        $starters = array_slice($courses, 0, $mainIdx);
        $mains = array_slice($courses, $mainIdx, $dessertIdx - $mainIdx);
        $desserts = array_slice($courses, $dessertIdx);

        self::parseMeal($mealObject, array($starters, $mains, $desserts));
    }

    /**
     * From a Meal and all of its courses, fill the database (fill the textline table)
     *
     * @param $meal    Meal the associated Meal
     * @param $courses \Maximethebault\Pdf2Table\XmlElements\Textline[][]
     */
    public static function parseMeal($meal, $courses) {
        $ordering = 0;
        for($i = 0; $i < 3; $i++) {
            /** @var $rawCourses \Maximethebault\Pdf2Table\XmlElements\Textline[] */
            $rawCourses = $courses[$i];
            if(!count($rawCourses)) {
                continue;
            }
            if($i == 0) {
                $courseObjectName = 'Starter';
            }
            elseif($i == 1) {
                $courseObjectName = 'Main';
            }
            else {
                $courseObjectName = 'Dessert';
            }
            $linkProperty = strtolower($courseObjectName) . '_id';
            $linkObjectName = 'Meal' . $courseObjectName;
            // we need to add the namespace info
            $courseObjectName = 'Maximethebault\\INSAMiamAPI\\Model\\' . $courseObjectName;
            $linkObjectName = 'Maximethebault\\INSAMiamAPI\\Model\\' . $linkObjectName;
            foreach($rawCourses as $rawCourse) {
                // when we've got fractions (1/2, 3/4, ...), don't split!
                $splitCourses = preg_split('`[^0-9]/[^0-9]`i', $rawCourse->getText());
                foreach($splitCourses as $course) {
                    // we also need to strip "*"
                    $course = trim($course, " \t\n\r\0\x0B*");
                    $similarCourses = $courseObjectName::first(array('select' => array('id, name, MATCH (name) AGAINST (?) AS score', $course)), array('order' => 'score desc'), array('conditions' => array("MATCH (name) AGAINST (?)", $course)));
                    $textlineObject = new Textline();
                    $textlineObject->meal_id = $meal->id;
                    $textlineObject->ordering = $ordering++;
                    $textlineObject->char_size = $rawCourse->getMaxCharSize();
                    $textlineObject->cell_size = $rawCourse->getParentCellSize();
                    $textlineObject->content = $course;
                    $textlineObject->$linkProperty = $similarCourses ? $similarCourses->id : 0;
                    $textlineObject->save();
                }
            }
            $textlineObject = new Textline();
            $textlineObject->meal_id = $meal->id;
            $textlineObject->ordering = $ordering++;
            $textlineObject->content = '';
            $textlineObject->save();
        }
    }

    /*
     * for($i = 0; $i < 3; $i++) {
            /** @var $rawCourses Textline[]
$rawCourses = $courses[$i];
    /** @var $courseObjectName Course
if($i == 0) {
$courseObjectName = 'Starter';
}
elseif($i == 1) {
    $courseObjectName = 'Main';
}
            else {
    $courseObjectName = 'Dessert';
}
            $linkProperty = strtolower($courseObjectName) . '_id';
            $linkObjectName = 'Meal' . $courseObjectName;
            // we need to add the namespace info
            $courseObjectName = 'Maximethebault\\INSAMiamAPI\\Model\\' . $courseObjectName;
            $linkObjectName = 'Maximethebault\\INSAMiamAPI\\Model\\' . $linkObjectName;
            foreach($rawCourses as $rawCourse) {
                $splitCourses = explode('/', $rawCourse->getText());
                foreach($splitCourses as $course) {
                    // we also need to strip "*"
                    $course = trim($course, " \t\n\r\0\x0B*");
                    /** @var $similarCourses Course[]
                    $similarCourses = $courseObjectName::find('all', array('conditions' => array("name LIKE ?", '%' . $course . '%')));
                    if(!count($similarCourses)) {
                        /** @var $courseObject Course
                        $courseObject = new $courseObjectName();
                        $courseObject->name = $course;
                        $courseObject->save();
                    }
                    else {
                        $courseObject = $similarCourses[0];
                    }
                    /** @var $link MealCourse
                    $link = new $linkObjectName();
                    $link->meal_id = $meal->id;
                    $link->$linkProperty = $courseObject->id;
                    $link->save();
                }
            }
        }
     */

    /**
     * Analyses a lunch & persists the results in database
     *
     * @param $initialDate DateTime lunch's date
     * @param $cell1Textlines \Maximethebault\Pdf2Table\XmlElements\Textline[] textlines of the first cell
     * @param $cell2Textlines \Maximethebault\Pdf2Table\XmlElements\Textline[] textlines of the second cell
     */
    private static function analyseLunch($initialDate, $cell1Textlines, $cell2Textlines) {
        $lunch = new Meal();
        $lunch->date = new DateTime($initialDate->format('Y-m-d'));
        $lunch->type = self::MEAL_TYPE_LUNCH;
        $lunch->validated = false;
        $lunch->closed = false;
        $lunch->save();

        $ordering = 1;
        foreach($cell1Textlines as $textline) {
            $textlineObject = new Textline();
            $textlineObject->meal_id = $lunch->id;
            $textlineObject->ordering = $ordering++;
            $textlineObject->char_size = $textline->getMaxCharSize();
            $textlineObject->cell_size = $textline->getParentCellSize();
            $textlineObject->content = $textline->getText();
            $textlineObject->save();
        }

        $textlineObject = new Textline();
        $textlineObject->meal_id = $lunch->id;
        $textlineObject->ordering = $ordering++;
        $textlineObject->content = '';
        $textlineObject->save();

        foreach($cell2Textlines as $textline) {
            $textlineObject = new Textline();
            $textlineObject->meal_id = $lunch->id;
            $textlineObject->ordering = $ordering++;
            $textlineObject->char_size = $textline->getMaxCharSize();
            $textlineObject->cell_size = $textline->getParentCellSize();
            $textlineObject->content = $textline->getText();
            $textlineObject->save();
        }
    }

    /**
     * Analyses a dinner & persists the results in database
     *
     * @param $initialDate DateTime dinner's date
     * @param $cellTextlines \Maximethebault\Pdf2Table\XmlElements\Textline[] textlines of the first cell
     */
    private static function analyseDinner($initialDate, $cellTextlines) {
        $dinner = new Meal();
        $dinner->date = new DateTime($initialDate->format('Y-m-d'));
        $dinner->type = self::MEAL_TYPE_DINNER;
        $dinner->validated = false;
        $dinner->closed = false;
        $dinner->save();

        $ordering = 1;
        foreach($cellTextlines as $textline) {
            $textlineObject = new Textline();
            $textlineObject->meal_id = $dinner->id;
            $textlineObject->ordering = $ordering++;
            $textlineObject->char_size = $textline->getMaxCharSize();
            $textlineObject->cell_size = $textline->getParentCellSize();
            $textlineObject->content = $textline->getText();
            $textlineObject->save();
        }
    }
}