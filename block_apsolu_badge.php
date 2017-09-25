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
 * Handles displaying the upcoming events block.
 *
 * @package    block_apsolu_badge
 * @copyright  2016 Université Rennes 2 <dsi-contact@univ-rennes2.fr>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

class block_apsolu_badge extends block_base {

    /**
     * Initialise the block.
     */
    public function init() {
        $this->title = get_string('pluginname', 'block_apsolu_badge');
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
        global $CFG, $DB, $OUTPUT, $PAGE, $USER;

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
            $sessions = array();
            $count_sessions = 0;

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
                $count_sessions++;
            }

            $enrols = array();
            $count_enrols = 0;
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
                $count_enrols++;
            }

            // Template data.
            $data = new stdClass();
            $data->wwwroot = $CFG->wwwroot;
            $data->courseid = $context->instanceid;
            $data->sessions = $sessions;
            $data->count_sessions = $count_sessions;
            $data->enrols = $enrols;
            if ($count_enrols !== 0) {
                $data->enrolid = $enrols[0]->id;
            }
            $data->count_enrols = $count_enrols;

            $template = 'block_apsolu_badge/teacher_block';
        } else {
            // This is a student.
            $sessions = array();
            $count_sessions = 0;

            $sql = "SELECT sessions.*, status.name, status.code".
                " FROM {apsolu_attendance_sessions} sessions".
                " LEFT JOIN {apsolu_attendance_presences} presences ON sessions.id = presences.sessionid".
                " LEFT JOIN {apsolu_attendance_statuses} status ON status.id = presences.statusid AND presences.studentid = :userid".
                " WHERE sessions.courseid = :courseid".
                " AND sessions.sessiontime < :time".
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
                    default:
                        $session->str_status = get_string('attendance_undefined', 'local_apsolu');
                        $session->css_status = 'text-left';
                }

                $sessions[] = $session;
                $count_sessions++;
            }

            // Template data.
            $data = new stdClass();
            $data->wwwroot = $CFG->wwwroot;
            $data->courseid = $context->instanceid;
            $data->sessions = $sessions;
            $data->count_sessions = $count_sessions;

            $template = 'block_apsolu_badge/student_block';
        }

        // Display template.
        $this->content->text = $OUTPUT->render_from_template($template, $data);

        return $this->content;
    }
}
