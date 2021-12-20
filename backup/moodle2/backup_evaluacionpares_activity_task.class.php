<?php

require_once($CFG->dirroot . '/mod/evaluacionpares/backup/moodle2/backup_evaluacionpares_stepslib.php'); // Because it exists (must)
require_once($CFG->dirroot . '/mod/evaluacionpares/backup/moodle2/backup_evaluacionpares_settingslib.php'); // Because it exists (optional)

/**
 * evaluacionpares backup task that provides all the settings and steps to perform one
 * complete backup of the activity
 */
class backup_evaluacionpares_activity_task extends backup_activity_task {

    /**
     * Define (add) particular settings this activity can have
     */
    protected function define_my_settings() {
        // No particular settings for this activity
    }

    /**
     * Define (add) particular steps this activity can have
     */
    protected function define_my_steps() {
        // evaluacionpares only has one structure step
        $this->add_step(new backup_evaluacionpares_activity_structure_step('evaluacionpares_structure', 'evaluacionpares.xml'));
    }

    /**
     * Code the transformations to perform in the activity in
     * order to get transportable (encoded) links
     */
    static public function encode_content_links($content) {
        global $CFG;

        $base = preg_quote($CFG->wwwroot,"/");

        // Link to the list of evaluacionparess
        $search = "/(" . $base . "\/mod\/evaluacionpares\/index.php\?id\=)([0-9]+)/";
        $content = preg_replace($search, '$@evaluacionparesINDEX*$2@$', $content);

        //Link to evaluacionpares view by moduleid
        $search = "/(" . $base . "\/mod\/evaluacionpares\/view.php\?id\=)([0-9]+)/";
        $content= preg_replace($search, '$@evaluacionparesVIEWBYID*$2@$', $content);

        return $content;
    }
}