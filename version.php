<?php
/**
 * Moodec Version file
 *
 * @package     local_moodec
 * @author      Vernon Spain - Originally Thomas Threadgold
 * @copyright   2015 LearningWorks Ltd
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$plugin->component = 'local_moodec';
$plugin->version   = 2025071600; // YYYYMMDDXX format, increment with each update.
$plugin->release   = '5.0.0 (Build: 2025071600)';
$plugin->requires  = 2023042400; // Moodle 4.3+ required (adjust to 2024041900 for Moodle 5.0).
$plugin->maturity  = MATURITY_BETA;
$plugin->dependencies = [
    'enrol_moodec' => 2024041900,
];
