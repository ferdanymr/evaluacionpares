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
 * Display information about all the mod_evaluatebypair modules in the requested course.
 *
 * @package     mod_evaluacionpares
 * @copyright   2021 Fernando Munoz <fernando_munoz@cuaieed.unam.mx>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require(__DIR__.'/../../config.php');
require_once('locallib.php');
require_once('localview/envio_form.php');

defined('MOODLE_INTERNAL') || die();

global $DB,$USER;

$id = optional_param('id', 0, PARAM_INT);
$envio->id = optional_param('env', 0, PARAM_INT);
$edit = optional_param('edit', 0, PARAM_INT);

$e  = optional_param('e', 0, PARAM_INT);

if ($id) {
    $cm             = get_coursemodule_from_id('evaluacionpares', $id, 0, false, MUST_EXIST);
    $course         = $DB->get_record('course', array('id' => $cm->course), '*', MUST_EXIST);
    $moduleinstance = $DB->get_record('evaluacionpares', array('id' => $cm->instance), '*', MUST_EXIST);
} else if ($e) {
    $moduleinstance = $DB->get_record('evaluacionpares', array('id' => $n), '*', MUST_EXIST);
    $course         = $DB->get_record('course', array('id' => $moduleinstance->course), '*', MUST_EXIST);
    $cm             = get_coursemodule_from_instance('evaluacionpares', $moduleinstance->id, $course->id, false, MUST_EXIST);
}

require_login($course, true, $cm);

$modulecontext = context_module::instance($cm->id);

$evaluacionpares =  new evaluacionpares($moduleinstance, $cm, $course);

if(!$envio->id || $edit){
    if(!$envio->id){
        $mform = new envio_form(new moodle_url('/mod/evaluacionpares/envio.php', 
            array('id' => $id)), array('attachmentopts' => $evaluacionpares->envio_archivo_options())); 
    }else{
        $mform = new envio_form(new moodle_url('/mod/evaluacionpares/envio.php', 
            array('id' => $id, 'env' => $envio->id, 'edit' => '1')), array('attachmentopts' => $evaluacionpares->envio_archivo_options())); 
    }

    if ($mform->is_cancelled()) {

        redirect(new moodle_url('/mod/evaluacionpares/view.php', array('id' => $cm->id, 'env' => $envio->id)));

    }else if ($data = $mform->get_data()) {
        $data->envios = '1';
        $data->calificacion = '0';
        $data->no_calificaciones = '0';
        $data->evaluacionpares_id = $evaluacionpares->id;
        $data->autor_id = $USER->id;
        
        if(!$envio->id){
            
            $data->id = $DB->insert_record('entrega', $data);

        }else{
            $data->id = $envio->id;
            $DB->update_record('entrega', $data);

        }

        file_save_draft_area_files($data->attachment_filemanager, $modulecontext->id, 'mod_evaluacionpares', 'submission_attachment',
            $data->id, $evaluacionpares->envio_archivo_options());

        redirect(new moodle_url('/mod/evaluacionpares/view.php', array('id' => $cm->id, 'env' => $data->id)));

    }else {
        $data = $evaluacionpares->get_envio_by_userId($USER->id);
        $envio = end($data);
        if (empty($envio->id)) {
            $envio = new stdClass;
            $envio->id = null;
        }

        $draftitemid = file_get_submitted_draft_itemid('attachment_filemanager');

        file_prepare_draft_area($draftitemid, $modulecontext->id, 'mod_evaluacionpares', 'submission_attachment', $envio->id,
                                $evaluacionpares->envio_archivo_options());

        $envio->attachment_filemanager = $draftitemid;

        $mform->set_data($envio);
    }
}

$PAGE->set_url(new moodle_url('/mod/evaluacionpares/envio.php', array('id' => $cm->id)));

$PAGE->set_title(get_string('pluginname', 'mod_evaluacionpares'));
$PAGE->set_heading(format_string($course->fullname));
$PAGE->set_context($modulecontext);

echo $OUTPUT->header();
echo $OUTPUT->heading(format_string($course->name));

if($envio->id && !$edit){

    $fs = get_file_storage();
    $files = $fs->get_area_files($modulecontext->id, 'mod_evaluacionpares', 'submission_attachment', $envio->id);
    
    $file = end($files);
    
    $data = $evaluacionpares->get_archivos_by_content_hash($file->get_contenthash(), $USER->id);
    echo '<h3>Su envio:</h3>';
    
    $data = end($data);
    $archivoUrl = new moodle_url("/draftfile.php/$data->contextid/user/draft/$data->itemid/$data->filename");
    
    echo '<a class="btn btn-secondary" href="'. $archivoUrl.'">'.$data->filename.'</a>';
    echo '<br>';
    echo '<br>';
    
    $url = new moodle_url('/mod/evaluacionpares/envio.php', array('id' => $cm->id, 'env'=>$envio->id, 'edit'=>'1'));
    echo '<a class="btn btn-primary" href="'. $url.'">'.get_string('setenvio','mod_evaluacionpares').'</a>';


}else{
    require_once('localview/main_form.php');
    $mform->display();
}

echo $OUTPUT->footer();