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
 * Library of internal classes and functions for module evaluacionpares
 *
 * All the evaluacionpares specific functions, needed to implement the module
 * logic, should go to here. Instead of having bunch of function named
 * evaluacionpares_something() taking the evaluacionpares instance as the first
 * parameter, we use a class evaluacionpares that provides all methods.
 *
 * @package     mod_evaluacionpares
 * @copyright   2021 Fernando Munoz <fernando_munoz@cuaieed.unam.mx>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

require_once(__DIR__.'/lib.php');     // we extend this library here
require_once($CFG->libdir . '/gradelib.php');   // we use some rounding and comparing routines here
require_once($CFG->libdir . '/filelib.php');

class evaluacionpares{

    const NO_ASPECTOS = 4;

    public $dbrecord;

    public $fase;

    public $cm;

    public $course;

    public $id;

    public $name;

    public $intro;

    public $introformat;

    public $calif_envio;

    public $calif_aprobatoria;

    public $calif_valoracion;

    public $calif_aprov_valoracion;

    public $no_decimales;

    public $no_archivos;

    public $tipo_arch = null;

    public $tam_max;

    public $instruccion_envio;

    public $instruccion_envioformat;

    public $instruccion_valoracion;

    public $instruccion_valoracionformat;

    public $no_revisiones;

    public $retro_conclusion;

    public $retro_conclusionformat;

    public $context;
    
    public function __construct(stdclass $dbrecord, $cm, $course, stdclass $context=null) {
        $this->dbrecord = $dbrecord;
        foreach ($this->dbrecord as $field => $value) {
            if (property_exists('evaluacionpares', $field)) {
                
                $this->{$field} = $value;

            }
        }

        if (is_null($cm) || is_null($course)) {
            throw new coding_exception('Must specify $cm and $course');
        }
        $this->course = $course;
        if ($cm instanceof cm_info) {
            $this->cm = $cm;
        } else {
            $modinfo = get_fast_modinfo($course);
            $this->cm = $modinfo->get_cm($cm->id);
        }
        if (is_null($context)) {
            $this->context = context_module::instance($this->cm->id);
        } else {
            $this->context = $context;
        }
    }

    
    public function url_vista() {
        global $CFG;
        return new moodle_url('/mod/evaluacionpares/view.php', array('id' => $this->cm->id));
    }

    public function get_envio_by_userId($userId){
        global $DB;
        return $DB->get_records_sql("SELECT * FROM {entrega} WHERE evaluacionpares_id = $this->id AND autor_id = $userId;");
    }

    public function get_archivos_by_content_hash($hash, $userid){
        global $DB;
        return $DB->get_records_sql("SELECT * FROM {files} WHERE contenthash = '$hash' AND userid = $userid ORDER BY id DESC;");
    }

    public function get_evaluaciones_completas_by_userId($userId){
        global $DB;
        return $DB->get_records_sql("SELECT * FROM {evaluacion_usuario} WHERE evaluacionpares_id = $this->id AND evaluador_id = $userId AND is_evaluado = 1;");
    }

    public function get_evaluacion_pendiente_by_userId($userId){
        global $DB;
        return $DB->get_records_sql("SELECT * FROM {evaluacion_usuario} WHERE evaluacionpares_id = $this->id AND evaluador_id = $userId AND is_evaluado = 0;");
    }

    public function get_envio_para_evaluar($userId){
        global $DB;
        return $DB->get_records_sql("SELECT * FROM {entrega} WHERE evaluacionpares_id = $this->id AND envio_listo = 1 AND autor_id != $userId ORDER BY no_calificaciones DESC LIMIT 1;");
    }

    public function get_envio_by_id($id){
        global $DB;
        return $DB->get_record_sql("SELECT * FROM {entrega} WHERE evaluacionpares_id = $this->id AND id = $id;");
    }

    public function edit_envio($data){
        global $USER, $DB;

        $data->envios             = '1';
        $data->envio_listo        = '0';
        $data->calificacion       = '0';
        $data->no_calificaciones  = '0';
        $data->evaluacionpares_id = $this->id;
        $data->autor_id           = $USER->id;
        
        if(is_null($data->id)){

            $data->id = $DB->insert_record('entrega', $data);

        }
        
        $data = file_postupdate_standard_filemanager($data, 'attachment', $this->filemanager_options(),
            $this->context, 'mod_evaluacionpares', 'submission_attachment', $data->id);
        
        if (empty($data->attachment)) {
                // Explicit cast to zero integer.
                $data->attachment = 0;
                $data->envios     = '0';
        }else{
            $data->envios = $data->attachment;
        }

        $DB->update_record('entrega', $data);
            
    }
    /**
     * Return the editor options for the submission content field.
     *
     * @return array
     */
    public function filemanager_options() {
        global $CFG;
        require_once($CFG->dirroot.'/repository/lib.php');

        return array(
            'subdirs' => 0,
            'maxbytes' => $this->tam_max,
            'maxfiles' => $this->no_archivos,
            'accepted_types'=> $this->tipo_arch,
            'return_types' => FILE_INTERNAL | FILE_EXTERNAL,
        );
    }

    public function prepare_envio(stdClass $record, $showauthor = true) {
        
        $submission         = new evaluacionpares_submission($this, $record, $showauthor);
        $submission->url    = $this->submission_url($record->id);
        return $submission;
    }
    
}