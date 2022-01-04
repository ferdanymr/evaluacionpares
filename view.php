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
 * Prints an instance of mod_evaluacionpares.
 *
 * @package     mod_evaluacionpares
 * @copyright   2021 Fernando Munoz <fernando_munoz@cuaieed.unam.mx>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require(__DIR__.'/../../config.php');
require_once('locallib.php');
require_once('localview/aspectos_form.php');

// Course_module ID, or
$id = optional_param('id', 0, PARAM_INT);
$envio->id = optional_param('env', 0, PARAM_INT);

// ... module instance id.
$e  = optional_param('e', 0, PARAM_INT);

$noAspectos = optional_param('no', 0, PARAM_INT);

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

/////////////////////////////////////////////////////////////////////////////////////////////////
//bloque para configurar la vista de criterios en dado caso de que la fase dea 0               //
/////////////////////////////////////////////////////////////////////////////////////////////////
if($moduleinstance->fase == 0){

    if($noAspectos){

        $noAspectos += 2; 
    
    }else{
    
        $noAspectos = evaluacionpares::NO_ASPECTOS;
    
    }

    $mform = new aspectos_form(new moodle_url('/mod/evaluacionpares/view.php', array('id' => $cm->id,'no' => $noAspectos)), $noAspectos);

    if ($mform->is_cancelled()) {
        //Si se cancela el formulario se regrasara a la pantalla principal del curso
        redirect(new moodle_url('/course/view.php', array('id'=>$course->id)));

    }else if ($fromform = $mform->get_data()) {
        
        $criterio       = new stdClass();
        $opcionCriterio = new stdClass();
    
        for($i = 1; $i <= $noAspectos-2; $i++){

            $des         = "descripcion$i";
            $descripcion = $fromform->$des;
            
            if(strlen($descripcion['text']) != 0){
                
                $criterio->criterio           = $descripcion['text'];
                $criterio->criterioformat     = $descripcion['format'];
                $criterio->evaluacionpares_id = $evaluacionpares->id;
                $idCriterio                   = $DB->insert_record('criterio_evaluacion', $criterio);
                
                for($j = 1; $j <= 4; $j++){

                    $definicion = "calif_def$i$j";
                    
                    if(strlen($fromform->$definicion) != 0){
                        $calificacion                            = "calif_envio$i$j";
                        $opcionCriterio->definicion              = $fromform->$definicion;
                        $opcionCriterio->calificacion            = $fromform->$calificacion;
                        $opcionCriterio->criterio_evaluacion_id = $idCriterio;

                        $DB->insert_record('opcion_criterio', $opcionCriterio);

                    }
    
                }

            }
        }
        
        $moduleinstance->fase = 1;
        $DB->update_record('evaluacionpares', $moduleinstance, $bulk=false);

        redirect(new moodle_url('/mod/evaluacionpares/view.php', array('id' => $cm->id)));
    
    }
    
}else{
    if(!$envio->id){
        $data = $evaluacionpares->get_envio_by_userId($USER->id);
        $envio = end($data);
        if (empty($envio->id)) {
            $envio = new stdClass;
            $envio->id = null;
        }
    }
}

$PAGE->set_url(new moodle_url('/mod/evaluacionpares/view.php', array('id' => $cm->id)));

$PAGE->set_title(get_string('pluginname', 'mod_evaluacionpares'));
$PAGE->set_heading(format_string($course->fullname));
$PAGE->set_context($modulecontext);

echo $OUTPUT->header();
echo $OUTPUT->heading(format_string($course->name));

if($moduleinstance->fase == 0){ 
    
    $mform->display();

}else{
    print_collapsible_region_start('','instrucciones-envio',get_string('param_inst','mod_evaluacionpares'));
    echo '<div class="row">';
    echo '	<div class="col-12">';
    echo "      <p>$moduleinstance->instruccion_envio</p>";
    echo '	</div>';
    echo '</div>';
    print_collapsible_region_end();
    
    print_collapsible_region_start('','envio',get_string('envio','mod_evaluacionpares'));

    if($envio->id){
        $fs         = get_file_storage();
        $files      = $fs->get_area_files($modulecontext->id, 'mod_evaluacionpares', 'submission_attachment', $envio->id);
        $file       = end($files);
        $data       = $evaluacionpares->get_archivos_by_content_hash($file->get_contenthash(), $USER->id);        
        $data       = current($data);
        $archivoUrl = new moodle_url("/draftfile.php/$data->contextid/user/draft/$data->itemid/$data->filename");

        echo '<div class="row">';
        echo '	<div class="col-12">';
        echo '      <br>';
        echo '      <p>'.$data->filename .' '. get_string('successenvio','mod_evaluacionpares').'</p>';
        echo '	</div>';
        echo '</div>';
        print_collapsible_region_end();
        echo '<br>';
        $url = new moodle_url('/mod/evaluacionpares/envio.php', array('id' => $cm->id, 'env' => $envio->id));
        echo '<a class="btn btn-primary" href="'. $url.'">'. get_string('verenvio','mod_evaluacionpares').'</a>';

    }else{

        echo '<div class="row">';
        echo '	<div class="col-12">';
        echo '      <p>'.get_string('noenvio','mod_evaluacionpares').'</p>';
        echo '	</div>';
        echo '</div>';
        print_collapsible_region_end();
        echo '<br>';
        $url = new moodle_url('/mod/evaluacionpares/envio.php', array('id' => $cm->id));
        echo '<a class="btn btn-primary" href="'. $url.'">'.get_string('addenvio','mod_evaluacionpares').'</a>';

    }

    $url = new moodle_url('/mod/evaluacionpares/aspectos.php', array('cmid' => $cm->id));
    echo '<a class="btn btn-primary" href="'. $url.'">'.get_string('setcriterios','mod_evaluacionpares').'</a>';
}

echo $OUTPUT->footer();