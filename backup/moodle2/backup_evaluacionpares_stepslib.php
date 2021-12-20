<?php

/**
 * Define all the backup steps that will be used by the backup_evaluacionpares_activity_task
 */
class backup_evaluacionpares_activity_structure_step extends backup_activity_structure_step {

    protected function define_structure() {

        // To know if we are including userinfo
        $userinfo = $this->get_setting_value('userinfo');

        // Define each element separated
        $evaluacionpares = new backup_nested_element('evaluacionpares',array('id'),array(
            'fase','name','intro','introformat','timecreated','timemodified','calif_envio',
            'calif_aprobatoria','calif_valoracion','calif_aprob_valoracion','no_decimales',
            'no_archivos','tipo_arch','tam_max','instruccion_envio','envioformat',
            'instruccion_valoracion','valoracionformat','no_revisiones','retro_conclusion',
            'conclusionformat','course'
        ));

        $criterios_evaluacions = new backup_nested_element('criterios_evaluacions');

        $criterios_evaluacion = new backup_nested_element('criterios_evaluacion', array('id'),array(
            'criterio','criterioformat'
        ));

        $opciones_criterios = new backup_nested_element('opciones_criterios');

        $opciones_criterio = new backup_nested_element('opciones_criterio',array('id'),array(
            'definicion','calificacion','criterios_evaluacion_id'
        ));

        // Build the tree
        $evaluacionpares->add_child($criterios_evaluacions);
        $criterios_evaluacions->add_child($criterios_evaluacion);
        
        $criterios_evaluacion->add_child($opciones_criterios);
        $opciones_criterios->add_child($opciones_criterio);
        
        // Define sources
        $evaluacionpares->set_source_table('evaluacionpares', array('id' => backup::VAR_ACTIVITYID));

        $criterios_evaluacion->set_source_sql('
            SELECT *
              FROM {criterios_evaluacion}
             WHERE evaluacionpares_id = ?',
            array(backup::VAR_PARENTID));

        $opciones_criterio->set_source_sql('
        SELECT *
          FROM {opciones_criterio}
         WHERE criterios_evaluacion_id = ?',
        array(backup::VAR_PARENTID));
        
        // All the rest of elements only happen if we are including user info
        if ($userinfo) {
            //$answer->set_source_table('evaluacionpares_answers', array('evaluacionparesid' => '../../id'));
        }

        // Define id annotations

        // Define file annotations
        $evaluacionpares->annotate_files('mod_evaluacionpares', 'intro', null);
        $evaluacionpares->annotate_files('mod_evaluacionpares', 'instruccion_envio', null);
        $evaluacionpares->annotate_files('mod_evaluacionpares', 'instruccion_valoracion', null);
        $evaluacionpares->annotate_files('mod_evaluacionpares', 'retro_conclusion', null);
        
        $opciones_criterios->annotate_files('mod_evaluacionpares', 'criterio', null);
        // Return the root element (evaluacionpares), wrapped into standard activity structure
        return $this->prepare_activity_structure($evaluacionpares);
    }
}