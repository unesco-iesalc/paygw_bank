<?php
// This file is part of the bank paymnts module for Moodle - http://moodle.org/
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
 * @package paygw_bank
 *
 * @copyright UNESCO/IESALC
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace paygw_bank;

defined('MOODLE_INTERNAL') || die();

require_once $CFG->libdir . '/formslib.php';


class attachtransfer_form extends \moodleform
{

    /**
     * form definition
     */
    public function definition()
    {
        global $CFG;
        $maxbytes = 0;
        if (!empty($CFG->maxbytes)) {
            $maxbytes = $CFG->maxbytes;
        }
        $accepted_types = array('document', 'image');
        $cfgallowedfiletypes = get_config('paygw_bank', 'allowedfiletypes');
        if (!empty($cfgallowedfiletypes)) {
            $accepted_types = explode(',', $cfgallowedfiletypes);
        }
        $mform = $this->_form;
        $mform->setDisableShortforms(true);
        $mform->addElement('hidden', 'confirm');
        $mform->setDefault('confirm', 2);
        $mform->setType('confirm', PARAM_INT);
        $mform->addElement('hidden', 'component');
        $mform->setType('component', PARAM_TEXT);
        $mform->addElement('hidden', 'paymentarea');
        $mform->setType('paymentarea', PARAM_TEXT);
        $mform->addElement('hidden', 'itemid');
        $mform->setType('itemid', PARAM_INT);
        $mform->addElement('hidden', 'description');
        $mform->setType('description', PARAM_TEXT);
        $mform->addElement(
            'filepicker',
            'userfile',
            get_string('file'),
            null,
            array('maxbytes' => $maxbytes, 'accepted_types' => $accepted_types)
        );
        $mform->addRule('userfile', null, 'required');

        $mform->addElement('submit', 'submitbutton', get_string('upload'));
    }
    public function validation($data, $files)
    {
        global $DB;
        $errors = parent::validation($data, $files);
        return $errors;
    }
}
