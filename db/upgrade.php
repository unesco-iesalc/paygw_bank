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
 * Upgrade script for paygw_bank.
 *
 * @package   paygw_bank
 * @copyright UNESCO/IESALC
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die();

/**
 * Upgrade the plugin.
 *
 * @param  int $oldversion the version we are upgrading from
 * @return bool always true
 */
function xmldb_paygw_bank_upgrade(int $oldversion): bool
{
    global $DB;
    
    $dbman = $DB->get_manager();

    if ($oldversion <  2023011801) {
        // Define key paymentid (foreign-unique) to be added to paygw_paypal.
        $table = new xmldb_table('paygw_bank');
        $field = new xmldb_field('totalamount', XMLDB_TYPE_NUMBER, '15, 5', null, XMLDB_NOTNULL, null, null, 'userid');
        // Alter the 'element' column to be characters, rather than text.
        $dbman->change_field_type($table, $field);
        upgrade_plugin_savepoint(true, 2023011801, 'paygw', 'bank');
    }
    return true;
}
