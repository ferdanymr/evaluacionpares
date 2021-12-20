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
 * Plugin version and other meta-data are defined here.
 *
 * @package     mod_evaluacionpares
 * @copyright   2021 Fernando Munoz <fernando_munoz@cuaieed.unam.mx>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Return if the plugin supports $feature.
 *
 * @param string $feature Constant representing the feature.
 * @return true | null True if the feature is supported, null otherwise.
 */
function evaluacionpares_supports($feature) {
    switch ($feature) {
        case FEATURE_GRADE_HAS_GRADE:
            return true;
        case FEATURE_MOD_INTRO:
            return true;
        case FEATURE_BACKUP_MOODLE2:
            return true;
        default:
            return null;
    }
}

/**
 * Saves a new instance of the mod_evaluacionpares into the database.
 *
 * Given an object containing all the necessary data, (defined by the form
 * in mod_form.php) this function will create a new instance and return the id
 * number of the instance.
 *
 * @param object $moduleinstance An object from the form.
 * @param mod_evaluacionpares_mod_form $mform The form.
 * @return int The id of the newly inserted record.
 */
function evaluacionpares_add_instance(stdclass $taller) {
    global $DB;
    $editor                               = $taller->instruccion_envio;
    $taller->fase                         = '0';
    $taller->timecreated                  = time();
    $taller->timemodified                 = $taller->timecreated;
    $taller->instruccion_envioformat      = $editor['format'];
    $taller->instruccion_envio            = $editor['text'];

    $editor                               = $taller->instruccion_valoracion;

    $taller->instruccion_valoracionformat = $editor['format'];
    $taller->instruccion_valoracion       = $editor['text'];

    $editor                               = $taller->retro_conclusion;

    $taller->retro_conclusionformat       = $editor['format'];
    $taller->retro_conclusion             = $editor['text'];

    $id = $DB->insert_record('evaluacionpares', $taller);

    return $id;
}

/**
 * Updates an instance of the mod_evaluacionpares in the database.
 *
 * Given an object containing all the necessary data (defined in mod_form.php),
 * this function will update an existing instance with new data.
 *
 * @param object $moduleinstance An object from the form in mod_form.php.
 * @param mod_evaluacionpares_mod_form $mform The form.
 * @return bool True if successful, false otherwise.
 */
function evaluacionpares_update_instance($moduleinstance, $mform = null) {
    global $DB;
    $editor = $moduleinstance->instruccion_envio;
    $moduleinstance->timemodified = time();
    $moduleinstance->id = $moduleinstance->instance;
    $moduleinstance->instruccion_envioformat             = $editor['format'];
    $moduleinstance->instruccion_envio       = $editor['text'];

    $editor = $moduleinstance->instruccion_valoracion;

    $moduleinstance->instruccion_valoracionformat        = $editor['format'];
    $moduleinstance->instruccion_valoracion  = $editor['text'];

    $editor = $moduleinstance->retro_conclusion;

    $moduleinstance->retro_conclusionformat        = $editor['format'];
    $moduleinstance->retro_conclusion        = $editor['text'];

    return $DB->update_record('evaluacionpares', $moduleinstance);
}

/**
 * Removes an instance of the mod_evaluacionpares from the database.
 *
 * @param int $id Id of the module instance.
 * @return bool True if successful, false on failure.
 */
function evaluacionpares_delete_instance($id) {
    global $DB;

    $exists = $DB->get_record('evaluacionpares', array('id' => $id));
    if (!$exists) {
        return false;
    }

    $DB->delete_records('evaluacionpares', array('id' => $id));

    return true;
}