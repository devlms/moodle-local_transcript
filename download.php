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

/**
 * Download an issued transcript as a PDF with its code and a QR code to the verify page.
 *
 * @package    local_transcript
 * @copyright  2026 DevLMS <contact@devlms.com>
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use local_transcript\local\pdf;

require_once(__DIR__ . '/../../config.php');

require_login(null, false);
$id = required_param('id', PARAM_INT);
$record = $DB->get_record('local_transcript', ['id' => $id], '*', MUST_EXIST);
$sys = context_system::instance();
require_capability($record->userid == $USER->id && !has_capability('local/transcript:manage', $sys)
    ? 'local/transcript:issueown' : 'local/transcript:manage', $sys);

pdf::send($record);
