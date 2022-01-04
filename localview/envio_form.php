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

//moodleform is defined in formslib.php
require_once("$CFG->libdir/formslib.php");

class envio_form extends moodleform {
    //Add elements to form
    function definition() {
        $mform = $this->_form;
        $attachmentopts = $this->_customdata['attachmentopts'];

        $mform->addElement('header', 'general', get_string('envio', 'mod_evaluacionpares'));

        $mform->addElement('text', 'titulo', get_string('titulo', 'mod_evaluacionpares'));
        $mform->setType('titulo', PARAM_TEXT);
        $mform->addRule('titulo', null, 'required', null, 'client');
        $mform->addRule('titulo', get_string('maximumchars', '', 255), 'maxlength', 255, 'client');

        $mform->addElement('textarea', "comentario", 'Comentario', array('cols' => 60, 'rows' => 3));

        $mform->addElement('static', 'filemanagerinfo', get_string('num_max_arch', 'mod_evaluacionpares'), 1);
        
        $mform->addElement('filemanager', 'attachment_filemanager', get_string('adjunto', 'evaluacionpares'),
                            null, $attachmentopts);
        $mform->addRule('attachment_filemanager', null, 'required', null, 'client');
        
        $this->add_action_buttons();
    }

    
    //Custom validation should be added here
    function validation($data, $files) {
        return array();
    }
}
