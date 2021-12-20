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

//$envio = file_prepare_standard_editor($envio, 'content', $evaluacionpares->envio_content_options(),
    //    $evaluacionpares->context, 'mod_evaluacionpares', 'submission_content', $envio->id);
//
    //$envio = file_prepare_standard_filemanager($envio, 'attachment', $evaluacionpares->envio_archivo_options(),
    //    $evaluacionpares->context, 'mod_evaluacionpares', 'submission_attachment', $envio->id);



    //$mform = new envio_form(new moodle_url('/mod/evaluacionpares/view.php', array('id' => $id)), array('current' => $submission, 'evaluacionpares' => $evaluacionpares,
    //    'contentopts' => $evaluacionpares->envio_content_options(), 'attachmentopts' => $evaluacionpares->envio_archivo_options()));

$mform = new envio_form(new moodle_url('/mod/evaluacionpares/envio.php', 
    array('id' => $id)), array('attachmentopts' => $evaluacionpares->envio_archivo_options()));

if ($mform->is_cancelled()) {
    
    redirect(new moodle_url('/mod/evaluacionpares/view.php', array('id' => $cm->id)));

}else if ($envio = $mform->get_data()) {
    $envio->envios = '1';
    $envio->calificacion = '0';
    $envio->no_calificaciones = '0';
    $envio->evaluacionpares_id = $evaluacionpares->id;
    $envio->autor_id = $USER->id;

    $envio->id = $DB->insert_record('entrega', $envio);

    file_save_draft_area_files($envio->attachment_filemanager, $modulecontext->id, 'mod_evaluacionpares', 'submission_attachment',
        $envio->id, $evaluacionpares->envio_archivo_options());
            
    redirect(new moodle_url('/mod/evaluacionpares/view.php', array('id' => $cm->id, 'env' => $envio->id)));
    //$envio = file_postupdate_standard_editor($envio, 'content', $evaluacionpares->envio_content_options(),
    //    $evaluacionpares->context, 'mod_evaluacionpares', 'submission_content', $envio->id);
    //
    //$envio = file_postupdate_standard_filemanager($envio, 'attachment', $evaluacionpares->envio_archivo_options(),
    //    $evaluacionpares->context, 'mod_evaluacionpares', 'submission_attachment', $envio->id);
}else {
    
    if(!$envio->id){
        $data = $evaluacionpares->get_envio_by_userId($USER->id);
        $envio = $data[1];
        if (empty($envio->id)) {
            $envio = new stdClass;
            $envio->id = null;
        }
    }
    var_dump($envio);

    $draftitemid = file_get_submitted_draft_itemid('attachment_filemanager');
    
    file_prepare_draft_area($draftitemid, $modulecontext->id, 'mod_evaluacionpares', 'submission_attachment', $envio->id,
                            $evaluacionpares->envio_archivo_options());
    
    $envio->attachment_filemanager = $draftitemid;
    
    $mform->set_data($envio);
}

$PAGE->set_url(new moodle_url('/mod/evaluacionpares/envio.php', array('id' => $cm->id)));

$PAGE->set_title(get_string('pluginname', 'mod_evaluacionpares'));
$PAGE->set_heading(format_string($course->fullname));
$PAGE->set_context($modulecontext);

echo $OUTPUT->header();
echo $OUTPUT->heading(format_string($course->name));

if($envio->id && !$edit){
    $out = array();
        
    $fs = get_file_storage();
    $files = $fs->get_area_files($modulecontext->id, 'mod_evaluacionpares', 'submission_attachment', $envio->id);
    foreach ($files as $file) {
        $filepath   = $file->get_filepath();
        $filename   = $file->get_filename();
        $fileurl    = moodle_url::make_pluginfile_url($modulecontext->id, 'mod_evaluacionpares', 'submission_attachment',
            $envio->id, $filepath, $filename, true);
        $embedurl   = moodle_url::make_pluginfile_url($modulecontext->id, 'mod_evaluacionpares', 'submission_attachment',
            $envio->id, $filepath, $filename, false);
        $embedurl   = new moodle_url($embedurl, array('preview' => 'bigthumb'));
        $type       = $file->get_mimetype();
        
        $linkhtml   = html_writer::link($fileurl, $image . substr($filepath, 1) . $filename);
        $linktxt    = "$filename [$fileurl]";

        $messagetext = file_rewrite_pluginfile_urls('/', 'pluginfile.php',
            $modulecontext->id, 'mod_evaluacionpares', 'submission_attachment', $envio->id);
        
        var_dump($messagetext);
    }
        echo '<h3>Su envio:</h3>';
        var_dump($linkhtml);
        echo '<br>';
        var_dump($linktxt);
        $url = new moodle_url('/mod/evaluacionpares/envio.php', array('id' => $cm->id, 'env'=>$envio->id, 'edit'=>'1'));
        echo '<a class="btn btn-primary" href="'. $url.'">Editar envio</a>';

        echo '<br>';


}else{
    require_once('localview/main_form.php');
    $mform->display();
}

echo $OUTPUT->footer();