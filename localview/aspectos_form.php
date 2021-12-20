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

class aspectos_form extends moodleform {
    //Add elements to form
    public function definition() {
        global $CFG;

        $mform = $this->_form; // Don't forget the underscore! 
        $noAspectos = $this->_customdata;

        $mform->addElement('html', '<h3>'. get_string('rub','mod_evaluacionpares') .'</h3>');
        
        $opciones = array();
        for($i = 100; $i >= 0; $i--){
            $opciones[$i] = $i;
        }

        for($i = 1; $i <= $noAspectos; $i++){
            $mform->addElement('header', "criterio$i", get_string('criterio', 'mod_evaluacionpares').' '.$i);
        
            $mform->addElement('editor', "descripcion$i", get_string('descrip', 'mod_evaluacionpares'));
            $mform->setType("descripcion$i", PARAM_RAW);

            $mform->addElement('hidden', 'descripcionid'.$i,'');   // value set by set_data() later

            for($j = 1; $j <= 4; $j++){
                $calificacion_definicion = array();
                $calificacion_definicion[] = $mform->createElement('select', "calif_envio$i$j", '', $opciones);
                $calificacion_definicion[] = $mform->createElement('textarea', "calif_def$i$j", '', array('cols' => 60, 'rows' => 3));
                $mform->addGroup($calificacion_definicion, "calif_definicion$i$j", get_string('calif_def', 'mod_evaluacionpares'), array(' '), false);
                $mform->setDefault("calif_envio$i$j","1");
                $mform->addElement('hidden', "opcionid$i$j",'');
            }
        }

        $mform->registerNoSubmitButton('addaspectos');
        $mform->addElement('submit', 'addaspectos', 'Espacio en blanco para dos criterios', array('addAspecto'=> 2));
        $mform->closeHeaderBefore('addaspectos');

        $this->add_action_buttons();
    }

    
    //Custom validation should be added here
    function validation($data, $files) {
        return array();
    }
}
