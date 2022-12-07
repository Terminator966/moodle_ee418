<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Library functions for timeline
 *
 * @package   block_upcoming_events2
 * @copyright 2018 Peter Dias
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die();

/**
 * Define constants to store the SORT user preference
 */
define('BLOCK_UPCOMING_EVENTS2_SORT_BY_DATES', 'sortbydates');
define('BLOCK_UPCOMING_EVENTS2_SORT_BY_COURSES', 'sortbycourses');

/**
 * Define constants to store the FILTER user preference
 */
define('BLOCK_UPCOMING_EVENTS2_FILTER_BY_NONE', 'all');
define('BLOCK_UPCOMING_EVENTS2_FILTER_BY_OVERDUE', 'overdue');
define('BLOCK_UPCOMING_EVENTS2_FILTER_BY_7_DAYS', 'next7days');
define('BLOCK_UPCOMING_EVENTS2_FILTER_BY_30_DAYS', 'next30days');
define('BLOCK_UPCOMING_EVENTS2_FILTER_BY_3_MONTHS', 'next3months');
define('BLOCK_UPCOMING_EVENTS2_FILTER_BY_6_MONTHS', 'next6months');
define('BLOCK_UPCOMING_EVENTS2_ACTIVITIES_LIMIT_DEFAULT', 5);

/**
 * Returns the name of the user preferences as well as the details this plugin uses.
 *
 * @return array
 */
function block_upcoming_events2_user_preferences() {
    $preferences['block_upcoming_events2_user_sort_preference'] = array(
        'null' => NULL_NOT_ALLOWED,
        'default' => BLOCK_UPCOMING_EVENTS2_SORT_BY_DATES,
        'type' => PARAM_ALPHA,
        'choices' => array(BLOCK_UPCOMING_EVENTS2_SORT_BY_DATES, BLOCK_UPCOMING_EVENTS2_SORT_BY_COURSES)
    );

    $preferences['block_upcoming_events2_user_filter_preference'] = array(
        'null' => NULL_NOT_ALLOWED,
        'default' => BLOCK_UPCOMING_EVENTS2_FILTER_BY_30_DAYS,
        'type' => PARAM_ALPHANUM,
        'choices' => array(
                BLOCK_UPCOMING_EVENTS2_FILTER_BY_NONE,
                BLOCK_UPCOMING_EVENTS2_FILTER_BY_OVERDUE,
                BLOCK_UPCOMING_EVENTS2_FILTER_BY_7_DAYS,
                BLOCK_UPCOMING_EVENTS2_FILTER_BY_30_DAYS,
                BLOCK_UPCOMING_EVENTS2_FILTER_BY_3_MONTHS,
                BLOCK_UPCOMING_EVENTS2_FILTER_BY_6_MONTHS
        )
    );

    $preferences['block_upcoming_events2_user_limit_preference'] = array(
        'null' => NULL_NOT_ALLOWED,
        'default' => BLOCK_UPCOMING_EVENTS2_ACTIVITIES_LIMIT_DEFAULT,
        'type' => PARAM_INT
    );

    return $preferences;
}
