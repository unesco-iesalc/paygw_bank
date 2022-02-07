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
 * Contains form to apply for PAYNL services through Sebsoft
 *
 * File         edit.php
 * Encoding     UTF-8
 *
 * @package     enrol_gwpayments
 *
 * @copyright   2021 Ing. R.J. van Dongen
 * @author      Ing. R.J. van Dongen <rogier@sebsoft.nl>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace paygw_bank;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/formslib.php');


class pay_form extends \moodleform {

    /**
     * form definition
     */
    public function definition() {
        $instructions=$config->instructionstext['text'] ;
        $mform = $this->_form;
        $mform->setDisableShortforms(true);
        $mform->addElement('hidden', 'confirm' );
        $mform->setDefault('confirm',1);
        $mform->addElement('hidden', 'component' );
        $mform->addElement('hidden', 'paymentarea' );
        $mform->addElement('hidden', 'itemid' );
        $mform->addElement('hidden', 'description' );
        $buttonarray=array();
        $mform->addElement('submit', 'submitbutton', get_string('start_process', 'paygw_bank'));

    }
    public function validation($data, $files) {
        global $DB;
        $errors = parent::validation($data, $files);
        return $errors;
    }


}