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
 * @package     mod_evaluacionpares
 * @copyright   2021 Fernando Munoz <fernando_munoz@cuaieed.unam.mx>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Define all the restore steps that will be used by the restore_workshop_activity_task
 */

/**
 * Structure step to restore one workshop activity
 */

 class restore_evaluacionpares_activity_structure_step extends restore_activity_structure_step {

protected function define_structure() {

    $paths = array();
    $userinfo = $this->get_setting_value('userinfo');

    $paths[]  = new restore_path_element('evaluacionpares', '/activity/evaluacionpares');

    $paths[] = new restore_path_element('criterios_evaluacion', '/activity/evaluacionpares/criterios_evaluacions/criterios_evaluacion');
    $paths[] = new restore_path_element('opciones_criterio', '/activity/evaluacionpares/criterios_evaluacions/criterios_evaluacion/opciones_criterios/opciones_criterio');

    // Return the paths wrapped into standard activity structure
    return $this->prepare_activity_structure($paths);
}

protected function process_evaluacionpares($data) {
    global $DB;

    $data = (object)$data;
    $oldid = $data->id;
    $data->course = $this->get_courseid();
    


    // insert the choice record
    $newitemid = $DB->insert_record('evaluacionpares', $data);
    // immediately after inserting "activity" record, call this
    $this->apply_activity_instance($newitemid);
}

protected function process_criterios_evaluacion($data) {
    global $DB;

    $data = (object)$data;
    $oldid = $data->id;

    $data->evaluacionpares_id = $this->get_new_parentid('evaluacionpares');
    
    $newitemid = $DB->insert_record('criterios_evaluacion', $data);
    $this->set_mapping('criterios_evaluacion', $oldid, $newitemid);
}

protected function process_opciones_criterio($data) {
    global $DB;

    $data = (object)$data;
    $oldid = $data->id;

    $data->criterios_evaluacion_id = $this->get_new_parentid('criterios_evaluacion');

    $newitemid = $DB->insert_record('opciones_criterio', $data);
    $this->set_mapping('opciones_criterio', $oldid, $newitemid);
}

protected function after_execute() {
    // Add choice related files, no need to match by itemname (just internally handled context)
    $this->add_related_files('mod_evaluacionpares', 'intro', null);
    $this->add_related_files('mod_evaluacionpares', 'instruccion_envio', null);
    $this->add_related_files('mod_evaluacionpares', 'instruccion_valoracion', null);
    $this->add_related_files('mod_evaluacionpares', 'retro_conclusion', null);
    $this->add_related_files('mod_evaluacionpares', 'criterio', null);
}
}