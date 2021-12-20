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

    public function get_envio_by_userId($userId){
        global $DB;
        return $DB->get_records_sql("SELECT * FROM {entrega} WHERE evaluacionpares_id = $this->id AND autor_id = $userId;");
    }

    /**
     * Return the editor options for the submission content field.
     *
     * @return array
     */
    public function envio_content_options() {
        global $CFG;
        require_once($CFG->dirroot.'/repository/lib.php');

        return array(
            'trusttext' => true,
            'subdirs' => false,
            'maxfiles' => $this->no_archivos,
            'maxbytes' => $this->tam_max,
            'context' => $this->context,
            //'return_types' => FILE_INTERNAL | FILE_EXTERNAL,
        );
    }

    public function envio_archivo_options() {
        global $CFG;
        require_once($CFG->dirroot.'/repository/lib.php');

        $options = array(
            'subdirs' => true,
            'maxfiles' => $this->no_archivos,
            'maxbytes' => $this->tam_max,
            //'return_types' => FILE_INTERNAL | FILE_CONTROLLED_LINK,
        );

        $filetypesutil = new \core_form\filetypes_util();
        $options['accepted_types'] = $filetypesutil->normalize_file_types($this->tipo_arch);

        return $options;
    }

    public function prepare_envio(stdClass $record, $showauthor = true) {
        
        $submission         = new evaluacionpares_submission($this, $record, $showauthor);
        $submission->url    = $this->submission_url($record->id);
        return $submission;
    }
    
}

/**
 * Common base class for submissions and example submissions rendering
 *
 * Subclasses of this class convert raw submission record from
 * evaluacionpares_submissions table (as returned by {@see evaluacionpares::get_submission_by_id()}
 * for example) into renderable objects.
 */
abstract class evaluacionpares_submission_base {

    /** @var bool is the submission anonymous (i.e. contains author information) */
    protected $anonymous;

    /* @var array of columns from evaluacionpares_submissions that are assigned as properties */
    protected $fields = array();

    /** @var evaluacionpares */
    protected $evaluacionpares;

    /**
     * Copies the properties of the given database record into properties of $this instance
     *
     * @param evaluacionpares $evaluacionpares
     * @param stdClass $submission full record
     * @param bool $showauthor show the author-related information
     * @param array $options additional properties
     */
    public function __construct(evaluacionpares $evaluacionpares, stdClass $submission, $showauthor = false) {

        $this->evaluacionpares = $evaluacionpares;

        foreach ($this->fields as $field) {
            if (!property_exists($submission, $field)) {
                throw new coding_exception('Submission record must provide public property ' . $field);
            }
            if (!property_exists($this, $field)) {
                throw new coding_exception('Renderable component must accept public property ' . $field);
            }
            $this->{$field} = $submission->{$field};
        }

        if ($showauthor) {
            $this->anonymous = false;
        } else {
            $this->anonymize();
        }
    }

    /**
     * Unsets all author-related properties so that the renderer does not have access to them
     *
     * Usually this is called by the contructor but can be called explicitely, too.
     */
    public function anonymize() {
        $authorfields = explode(',', implode(',', \core_user\fields::get_picture_fields()));
        foreach ($authorfields as $field) {
            $prefixedusernamefield = 'author' . $field;
            unset($this->{$prefixedusernamefield});
        }
        $this->anonymous = true;
    }

    /**
     * Does the submission object contain author-related information?
     *
     * @return null|boolean
     */
    public function is_anonymous() {
        return $this->anonymous;
    }
}

/**
 * Renderable object containing a basic set of information needed to display the submission summary
 *
 * @see evaluacionpares_renderer::render_evaluacionpares_submission_summary
 */
class evaluacionpares_submission_summary extends evaluacionpares_submission_base implements renderable {

    /** @var int */
    public $id;
    /** @var string */
    public $title;
    /** @var string graded|notgraded */
    public $status;
    /** @var int */
    public $timecreated;
    /** @var int */
    public $timemodified;
    /** @var int */
    public $authorid;
    /** @var string */
    public $authorfirstname;
    /** @var string */
    public $authorlastname;
    /** @var string */
    public $authorfirstnamephonetic;
    /** @var string */
    public $authorlastnamephonetic;
    /** @var string */
    public $authormiddlename;
    /** @var string */
    public $authoralternatename;
    /** @var int */
    public $authorpicture;
    /** @var string */
    public $authorimagealt;
    /** @var string */
    public $authoremail;
    /** @var moodle_url to display submission */
    public $url;

    /**
     * @var array of columns from evaluacionpares_submissions that are assigned as properties
     * of instances of this class
     */
    protected $fields = array(
        'id', 'title', 'timecreated', 'timemodified',
        'authorid', 'authorfirstname', 'authorlastname', 'authorfirstnamephonetic', 'authorlastnamephonetic',
        'authormiddlename', 'authoralternatename', 'authorpicture',
        'authorimagealt', 'authoremail');
}

/**
 * Renderable object containing all the information needed to display the submission
 *
 * @see evaluacionpares_renderer::render_evaluacionpares_submission()
 */
class evaluacionpares_submission extends evaluacionpares_submission_summary implements renderable {

    /** @var string */
    public $content;
    /** @var int */
    public $contentformat;
    /** @var bool */
    public $contenttrust;
    /** @var array */
    public $attachment;

    /**
     * @var array of columns from evaluacionpares_submissions that are assigned as properties
     * of instances of this class
     */
    protected $fields = array(
        'id', 'title', 'timecreated', 'timemodified', 'content', 'contentformat', 'contenttrust',
        'attachment', 'authorid', 'authorfirstname', 'authorlastname', 'authorfirstnamephonetic', 'authorlastnamephonetic',
        'authormiddlename', 'authoralternatename', 'authorpicture', 'authorimagealt', 'authoremail');
}


function show_addcase_form() {
    require_once('localview/addcase_form.php');
}

function show_main_form() {
    
}

