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

// Id del curso
$id = optional_param('id', 0, PARAM_INT);

//Si hay un envio aqui capturaremos su id
$envio->id = optional_param('env', 0, PARAM_INT);

// ... module instance id.
$e  = optional_param('e', 0, PARAM_INT);

//contador del numero de aspectos
$noAspectos = optional_param('no', 0, PARAM_INT);

$confirm_env = optional_param('confirm_env', 0, PARAM_INT);

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
//bloque para configurar la vista de criterios en dado caso de que la fase sea 0               //
/////////////////////////////////////////////////////////////////////////////////////////////////
if($evaluacionpares->fase == 0){

    //validamos si por get se mando el numero de aspectos al cual le agregaremos 2 más
    //si no se tienen se asignaran por defecto 2
    if($noAspectos){

        $noAspectos += 2; 
    
    }else{
    
        $noAspectos = evaluacionpares::NO_ASPECTOS;
    
    }

    //se define el formulario con la url a la que mandara los datos a la hora de hacer submit
    //los parametros enviados son el id del curso y el numero de aspectos actual
    // al formulario por aparte le mandamos tambien el numero de aspectos
    $mform = new aspectos_form(new moodle_url('/mod/evaluacionpares/view.php', array('id' => $cm->id,'no' => $noAspectos)), $noAspectos);

    if ($mform->is_cancelled()) {
        //Si se cancela el formulario se regrasara a la pantalla principal del curso
        redirect(new moodle_url('/course/view.php', array('id'=>$course->id)));

    }else if ($fromform = $mform->get_data()) {
        //si se hace submit se preparan los datos para insertarlos en la Base de Datos        
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

/////////////////////////////////////////////////////////////////////////////////////////////////
//bloque para configurar la vista de criterios en dado caso de que la fase sea 1               //
/////////////////////////////////////////////////////////////////////////////////////////////////
}else{
    //primero verificamos si el usuario ya hizo un envio o no para modificar la vista de acuerdo a su envio
    if(!$envio->id){
        $data = $evaluacionpares->get_envio_by_userId($USER->id);
        $envio = end($data);
        if (empty($envio->id)) {
            $envio = new stdClass;
            $envio->id = null;
        }
    }
}

//seteamos la url de la pagina
$PAGE->set_url(new moodle_url('/mod/evaluacionpares/view.php', array('id' => $cm->id)));
//seteamos el titulo de la pagina
$PAGE->set_title(get_string('pluginname', 'mod_evaluacionpares'));
$PAGE->set_heading(format_string($course->fullname));
$PAGE->set_context($modulecontext);

echo $OUTPUT->header();
echo $OUTPUT->heading(format_string($course->name));

//Si la fase es 0 siginifa configuracion entonces mostramos el formulario
if($evaluacionpares->fase == 0){ 
    
    $mform->display();

}else{
    if($confirm_env == 1){

        $urlConfirm = new moodle_url('/mod/evaluacionpares/view.php', array('id' => $cm->id, 'confirm_env' => '2'));
        $urlCancel = new moodle_url('/mod/evaluacionpares/view.php', array('id' => $cm->id));
        echo '<div class="row">';
        echo '	<div class="col-10 offset-1 text-center">';
        echo "      <h3>¿Estas seguro de pasar a evaluar alumnos?</h3>";
        echo "      <p>Una vez que empieces a evaluar a tus compañeros tu trabajo no va poder ser modificable de ninguna manera</p>";
        echo '      <a class="btn btn-secondary" href="'. $urlCancel.'">'. 'Cancelar' .'</a>';
        echo '      <a class="btn btn-primary" href="'. $urlConfirm.'">'. 'Confirmar' .'</a>';
        echo '	</div>';
        echo '</div>';

    }else if($confirm_env == 2){
        
        $envio->envio_listo = '1';
        $DB->update_record('entrega', $envio);
        redirect(new moodle_url('/mod/evaluacionpares/view.php', array('id' => $cm->id)));

    }else if($envio->envio_listo != 1){
        
        //si  no configuramos la vista para mostrar los envios
        print_collapsible_region_start('','instrucciones-envio',get_string('param_inst','mod_evaluacionpares'));
        echo '<div class="row">';
        echo '	<div class="col-12">';
        echo "      <p>$moduleinstance->instruccion_envio</p>";
        echo '	</div>';
        echo '</div>';
        print_collapsible_region_end();
        
        print_collapsible_region_start('','envio',get_string('envio','mod_evaluacionpares'));

        //verificamos si el alumno ya tienen un envio o aun no
        if($envio->id){
            //traemos los envios hechos
            $fs         = get_file_storage();
            //seleccionamos los de area evaluacionpares y el id del envio
            $files      = $fs->get_area_files($modulecontext->id, 'mod_evaluacionpares', 'envio_filemanager', $envio->id);
            //traemos el ultimo registro
            $file       = end($files);
            //traemos el nombre y un mensaje de que su envio ha sido registrado con exito
            echo '<div class="row">';
            echo '	<div class="col-12">';
            echo '      <br>';
            echo '      <p>'. $file->get_filename() .' '. get_string('successenvio','mod_evaluacionpares').'</p>';
            echo '	</div>';
            echo '</div>';
            //mostramos un boton para que el usuario pueda ver su envio
            $url = new moodle_url('/mod/evaluacionpares/envio.php', array('id' => $cm->id, 'env' => $envio->id));
            echo '<a class="btn btn-primary" href="'. $url.'">'. get_string('verenvio','mod_evaluacionpares').'</a>';
            print_collapsible_region_end();
            echo '<br>';

            print_collapsible_region_start('','calificar','Calificar otros envios');
            echo '<div class="row">';
            echo '	<div class="col-12">';
            echo '      <br>';
            echo '      <p>Una vez realizado el envio, puedes proceder a evaluar a tus compañeros</p>';
            echo '	</div>';
            echo '</div>';

            $url = new moodle_url('/mod/evaluacionpares/view.php', array('id' => $cm->id, 'confirm_env' => '1'));
            echo '<a class="btn btn-primary" href="'. $url.'">'. 'Evaluar trabajos' .'</a>';
            print_collapsible_region_end();

        }else{

            //si no tiene envio configuramos la vista para desplegar un mensaje de que aun no tiene ningun envio
            echo '<div class="row">';
            echo '	<div class="col-12">';
            echo '      <p>'.get_string('noenvio','mod_evaluacionpares').'</p>';
            echo '	</div>';
            echo '</div>';
            //mostramos un boton para que pueda añadir su envio
            $url = new moodle_url('/mod/evaluacionpares/envio.php', array('id' => $cm->id));
            echo '<a class="btn btn-primary" href="'. $url.'">'.get_string('addenvio','mod_evaluacionpares').'</a>';
            print_collapsible_region_end();

        }

    }else if($envio->envio_listo == 1){
        
        $evaluacionesUser    = $evaluacionpares->get_evaluaciones_completas_by_userId($USER->id);
        $noEvaluaciones      = count($evaluacionesUser);
        $evaluacionPendiente = $evaluacionpares->get_evaluacion_pendiente_by_userId($USER->id);
        $evaluacionPendiente = current($evaluacionPendiente);
        $envio               = $evaluacionpares->get_envio_by_userId($USER->id);
        $envio               = end($envio);

        //si  no configuramos la vista para mostrar las instrucciones de evaluacion
        print_collapsible_region_start('','instrucciones-evaluacion','Instrucciones evaluacion');
        echo '<div class="row">';
        echo '	<div class="col-12">';
        echo "      <p>$evaluacionpares->instruccion_valoracion</p>";
        echo '	</div>';
        echo '</div>';
        print_collapsible_region_end();

        print_collapsible_region_start('','evaluaciones-hechas','Evaluaciones realizadas');
        echo '<div class="row">';
        echo '	<div class="col-12">';
        echo "      <p>Trabajos evaluados: $noEvaluaciones de $evaluacionpares->no_revisiones</p>";
        echo '	</div>';
        echo '</div>';
        
        if($evaluacionPendiente){

            $url = new moodle_url('/mod/evaluacionpares/evaluaciones.php', array('id' => $cm->id, 'trabajo' => $evaluacionPendiente->entrega_id));

        }else{

            $url = new moodle_url('/mod/evaluacionpares/evaluaciones.php', array('id' => $cm->id));

        }
        
        echo '<a class="btn btn-primary" href="'. $url.'">'. 'Evaluar trabajo' .'</a>';
        print_collapsible_region_end();
        echo  '<br>';

        print_collapsible_region_start('','calificacion-obtenidas','Calificacion');
        echo '<div class="row">';
        echo '	<div class="col-12">';
        if($envio->no_calificaciones == $evaluacionpares->no_revisiones && $noEvaluaciones == $evaluacionpares->no_revisiones){
            echo "      <p>Su calificacion final es:</p>";
            echo "      <p>$envio->calificacion</p>";
        }else{
            echo "      <p>Cuando obtenga y realice el numero de evaluaciones que se requiere se le mostrara su calificacion</p>";
            echo "      <p>Evaluaciones recibidas $envio->no_calificaciones de $evaluacionpares->no_revisiones</p>";
        }
        echo '	</div>';
        echo '</div>';
        print_collapsible_region_end();
    }
    
    echo '<br>';
    $url = new moodle_url('/mod/evaluacionpares/aspectos.php', array('cmid' => $cm->id));
    echo '<a class="btn btn-primary" href="'. $url.'">'.get_string('setcriterios','mod_evaluacionpares').'</a>';
}

echo $OUTPUT->footer();