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
 * Bloc affichant dans un cours un résumé des présences aux enseignants et les présences de l'étudiant.
 *
 * @package    block_apsolu_course
 * @copyright  2016 Université Rennes 2 <dsi-contact@univ-rennes2.fr>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Classe représentant le bloc block_apsolu_course.
 *
 * @copyright 2016 Université Rennes 2 <dsi-contact@univ-rennes2.fr>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class block_apsolu_course extends block_base {

    /**
     * Initialise the block.
     */
    public function init() {
        $this->title = get_string('title', 'block_apsolu_course');
    }

    /**
     * Applicable only in course context.
     */
    public function applicable_formats() {
        return array('course-view' => true);
    }

    /**
     * No configuration.
     */
    public function has_config() {
        return false;
    }

    /**
     * No configuration.
     */
    public function instance_allow_config() {
        return false;
    }

    /**
     * Only one instance.
     */
    public function instance_allow_multiple() {
        return false;
    }

    /**
     * Return the content of this block.
     *
     * @return stdClass the content
     */
    public function get_content() {
        global $CFG, $OUTPUT;

        if ($this->content !== null) {
            return $this->content;
        }

        $context = context_course::instance($this->page->course->id);
        if ($context->contextlevel != CONTEXT_COURSE) {
            return;
        }

        $this->content = new stdClass;
        $this->content->text = '';

        if (has_capability('moodle/course:update', $context)) {
            // This is a teacher.
            $data = $this->get_teacher_content($context);
            $template = 'block_apsolu_course/teacher_block';
        } else {
            // This is a student.
            $data = $this->get_student_content($context);
            $template = 'block_apsolu_course/student_block';
        }

        $data->wwwroot = $CFG->wwwroot;
        $data->courseid = $context->instanceid;

        // Display template.
        $this->content->text = $OUTPUT->render_from_template($template, $data);

        return $this->content;
    }

    /**
     * Return the content of this block for a student.
     *
     * @param stdClass $context Contexte dans lequel le bloc est utilisé.
     *
     * @return stdClass the content
     */
    private function get_student_content(stdClass $context) {
        global $DB, $USER;

        $sessions = array();
        $countsessions = 0;

        $sql = "SELECT sessions.*, status.name, status.code".
            " FROM {apsolu_attendance_sessions} sessions".
            " LEFT JOIN {apsolu_attendance_presences} presences ON sessions.id = presences.sessionid".
            " LEFT JOIN {apsolu_attendance_statuses} status ON status.id = presences.statusid".
            " WHERE sessions.courseid = :courseid".
            " AND sessions.sessiontime < :time".
            " AND (presences.studentid = :userid OR presences.studentid IS NULL)".
            " ORDER BY sessions.sessiontime";
        $params = array(
            'courseid' => $context->instanceid,
            'time' => (time() + 8 * 7 * 24 * 60 * 60),
            'userid' => $USER->id,
            );
        foreach ($DB->get_records_sql($sql, $params) as $session) {
            $session->str_date = userdate($session->sessiontime, '%d %b %y');

            switch ($session->name) {
                case 'present':
                    $session->str_status = get_string($session->code, 'local_apsolu');
                    $session->css_status = 'text-success';
                    break;
                case 'late':
                    $session->str_status = get_string($session->code, 'local_apsolu');
                    $session->css_status = 'text-warning';
                    break;
                case 'excused':
                    $session->str_status = get_string($session->code, 'local_apsolu');
                    $session->css_status = 'text-info';
                    break;
                case 'absent':
                    $session->str_status = get_string($session->code, 'local_apsolu');
                    $session->css_status = 'text-danger';
                    break;
                default:
                    $session->str_status = get_string('attendance_undefined', 'local_apsolu');
                    $session->css_status = 'text-left';
            }

            $sessions[] = $session;
            $countsessions++;
        }

        // Template data.
        $data = new stdClass();
        $data->courseid = $context->instanceid;
        $data->sessions = $sessions;
        $data->count_sessions = $countsessions;

        return $data;
    }

    /**
     * Return the content of this block for a teacher.
     *
     * @param stdClass $context Contexte dans lequel le bloc est utilisé.
     *
     * @return stdClass the content
     */
    private function get_teacher_content(stdClass $context) {
        global $DB;

        $sessions = array();
        $countsessions = 0;

        $sql = "SELECT sessions.*, COUNT(presences.id) AS presences".
            " FROM {apsolu_attendance_sessions} sessions".
            " LEFT JOIN {apsolu_attendance_presences} presences ON sessions.id = presences.sessionid AND presences.statusid IN (1,2)".
            " WHERE sessions.courseid = :courseid".
            " AND sessions.sessiontime < :time".
            " GROUP BY sessions.id".
            " ORDER BY sessions.sessiontime";
        $params = array(
            'courseid' => $context->instanceid,
            'time' => (time() + 8 * 7 * 24 * 60 * 60),
            );
        foreach ($DB->get_records_sql($sql, $params) as $session) {
            $session->str_date = userdate($session->sessiontime, '%d %b %y');
            $sessions[] = $session;
            $countsessions++;
        }

        $enrols = array();
        $countenrols = 0;
        $params = array(
            'enrol' => 'select',
            'status' => 0,
            'courseid' => $this->page->course->id,
            );

        foreach ($DB->get_records('enrol', $params) as $enrol) {
            if (empty($enrol->name) === true) {
                $enrol->name = 'Gérer mes étudiants';
            }

            $enrols[] = $enrol;
            $countenrols++;
        }

        // Template data.
        $data = new stdClass();
        $data->sessions = $sessions;
        $data->count_sessions = $countsessions;
        $data->enrols = $enrols;
        if ($countenrols !== 0) {
            $data->enrolid = $enrols[0]->id;
        }
        $data->count_enrols = $countenrols;

        return $data;
    }
}
