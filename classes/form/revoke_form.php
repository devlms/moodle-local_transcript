<?php
// This file is part of Moodle - https://moodle.org/
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
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

namespace local_transcript\form;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/formslib.php');

/**
 * Reason for revoking a transcript.
 *
 * @package    local_transcript
 * @copyright  2026 DevLMS <contact@devlms.com>
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class revoke_form extends \moodleform {
    /**
     * Form fields.
     */
    protected function definition() {
        $f = $this->_form;
        $f->addElement('hidden', 'id', $this->_customdata['id']);
        $f->setType('id', PARAM_INT);
        $f->addElement('static', 'confirm', '', get_string('revokeconfirm', 'local_transcript', s($this->_customdata['code'])));
        $f->addElement('textarea', 'reason', get_string('revokereason', 'local_transcript'), ['rows' => 3, 'cols' => 50]);
        $f->setType('reason', PARAM_TEXT);
        $f->addRule('reason', null, 'required', null, 'client');
        $this->add_action_buttons(true, get_string('revoke', 'local_transcript'));
    }
}
