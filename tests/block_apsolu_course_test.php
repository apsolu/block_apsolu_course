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
 * Test block_apsolu_course class.
 *
 * @package    block_apsolu_course
 * @copyright  2020 Université Rennes 2 <dsi-contact@univ-rennes2.fr>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_apsolu_course;

use advanced_testcase;
use block_apsolu_course;

defined('MOODLE_INTERNAL') || die();

global $CFG;

require_once($CFG->dirroot.'/blocks/moodleblock.class.php');
require_once($CFG->dirroot.'/blocks/apsolu_course/block_apsolu_course.php');

/**
 * Classe PHPUnit permettant de tester la classe block_apsolu_course.
 *
 * @copyright  2020 Université Rennes 2 <dsi-contact@univ-rennes2.fr>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class block_apsolu_course_test extends advanced_testcase {
    /**
     * Teste la méthode init().
     *
     * @covers \block_apsolu_course::init()
     *
     * @return void
     */
    public function test_init() {
        $block = new block_apsolu_course();
        $block->init();

        $this->assertSame(get_string('title', 'block_apsolu_course'), $block->title);
    }
}
