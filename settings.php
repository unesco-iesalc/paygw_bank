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
 * Settings for the bank payment gateway
 *
 * @package   paygw_bank
 * @copyright UNESCO/IESALC
 * @author    Carlos Vicente Corral <c.vicente@unesco.org>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

if ($ADMIN->fulltree) {

    $settings->add(new admin_setting_heading('paygw_bank_settings', '', get_string('pluginname_desc', 'paygw_bank')));
    $settings->add(new admin_setting_configcheckbox('paygw_bank/usercanuploadfiles', get_string('allow_users_add_files', 'paygw_bank'), '', 0));
    $settings->add(new admin_setting_configtext('paygw_bank/allowedfiletypes', get_string('allowed_file_types', 'paygw_bank'), get_string('allowed_file_types_help', 'paygw_bank'), '', PARAM_RAW));
    $settings->add(new admin_setting_configcheckbox('paygw_bank/allowusercancel', get_string('allow_users_cancel_payments', 'paygw_bank'), '', 0));
   
    $settings->add(new admin_setting_configtext('paygw_bank/maxnumberfiles', get_string('max_number_of_files', 'paygw_bank'), '', '3', PARAM_INT));
    $settings->add(new admin_setting_configtext('paygw_bank/aditionalcurrencies', get_string('additional_currencies', 'paygw_bank'), get_string('additional_currencies_help', 'paygw_bank'), '', PARAM_TAGLIST));
    $settings->add(new admin_setting_configcheckbox('paygw_bank/sendconfmail', get_string('send_confirmation_mail', 'paygw_bank'), '', 0));
    $settings->add(new admin_setting_configcheckbox('paygw_bank/senddenmail', get_string('send_denied_mail', 'paygw_bank'), '', 0));
    $settings->add(new admin_setting_heading('paygw_bank/notemails', get_string('email_notifications', 'paygw_bank'), get_string('email_notifications_help', 'paygw_bank'),));
    $settings->add(new admin_setting_configtext('paygw_bank/notificationsaddress', get_string('email_to_notify', 'paygw_bank'), '', '', PARAM_EMAIL));
    $settings-
    $settings->add(new admin_setting_configcheckbox('paygw_bank/sendnewrequestmail', get_string('send_new_request_mail', 'paygw_bank'), '', 0));
    $settings->add(new admin_setting_configcheckbox('paygw_bank/sendnewattachmentsmail', get_string('send_new_attachments_mail', 'paygw_bank'), '', 0));
    $settings->add(new admin_setting_configcheckbox('paygw_bank/senconfirmailtosupport', get_string('send_confirm_mail_to_support', 'paygw_bank'), '', 0));
    
   
    \core_payment\helper::add_common_gateway_settings($settings, 'paygw_bank');
}
$systemcontext = \context_system::instance();
    $node = new admin_category('bank', get_string('pluginname', 'paygw_bank'));
    $ADMIN->add('root', $node);
    $ADMIN->add(
        'bank', new admin_externalpage(
            'managetransfers',
            get_string('manage', 'paygw_bank'),
            new moodle_url('/payment/gateway/bank/manage.php'), 'paygw/bank:managepayments'
        )
    );


