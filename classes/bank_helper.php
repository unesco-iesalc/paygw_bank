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
 * Contains helper class to work with PayPal REST API.
 *
 * @package   paygw_bank
 * @copyright UNESCO/IESALC
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace paygw_bank;

use curl;
use core_user;

defined('MOODLE_INTERNAL') || die();

require_once $CFG->libdir . '/filelib.php';

use core_payment\helper as payment_helper;
use stdClass;


class bank_helper
{


    public static function get_openbankentry($itemid, $userid): \stdClass
    {
        global $DB;
        $record = $DB->get_record('paygw_bank', ['itemid' => $itemid, 'userid' => $userid, 'status' => 'P']);
        return $record;
    }
    public static function check_hasfiles($id): \stdClass
    {
        global $DB, $USER;
        $transaction = $DB->start_delegated_transaction();
        $record = $DB->get_record('paygw_bank', ['id' => $id]);
        if ($record->userid == $USER->id) {
            $record->hasfiles = 1;
            $DB->update_record('paygw_bank', $record);
            return $record;
        }
        return null;
    }
    public static function aprobe_pay($id): \stdClass
    {
        global $DB, $USER;
        $transaction = $DB->start_delegated_transaction();
        $record = $DB->get_record('paygw_bank', ['id' => $id]);
        $config = (object) payment_helper::get_gateway_configuration($record->component, $record->paymentarea, $record->itemid, 'bank');
        $payable = payment_helper::get_payable($record->component, $record->paymentarea, $record->itemid);
        $paymentid = payment_helper::save_payment(
            $payable->get_account_id(),
            $record->component,
            $record->paymentarea,
            $record->itemid,
            (int) $record->userid,
            $record->totalamount,
            $payable->get_currency(),
            'bank'
        );
        $record->timechecked = time();
        $record->status = 'A';
        $record->usercheck = $USER->id;
        $record->paymentid = $paymentid;
        $DB->update_record('paygw_bank', $record);
        payment_helper::deliver_order($record->component, $record->paymentarea, $record->itemid, $paymentid, (int) $record->userid);
        $send_email = get_config('paygw_bank', 'sendconfmail');
        if ($send_email) {
            $supportuser = core_user::get_support_user();
            $paymentuser=bank_helper::get_user($record->userid);
            $fullname = fullname($paymentuser, true);
            $userlang=$USER->lang;
            $USER->lang=$paymentuser->lang;
            $subject = get_string('mail_confirm_pay_subject', 'paygw_bank');
            $contentmessage = new stdClass;
            $contentmessage->username = $fullname;
            $contentmessage->code = $record->code;
            $contentmessage->concept = $record->description;
            $mailcontent = get_string('mail_confirm_pay', 'paygw_bank', $contentmessage);
            email_to_user($paymentuser, $supportuser, $subject, $mailcontent);
            $USER->lang=$userlang;
        }
        $send_email = get_config('paygw_bank', 'senconfirmailtosupport');
        $emailaddress=get_config('paygw_bank', 'notificationsaddress');
        if ($send_email) {
            $supportuser = core_user::get_support_user();
            $subject = get_string('email_notifications_subject_confirm', 'paygw_bank');
             $contentmessage = new stdClass;
            $contentmessage->code = $record->code;
            $contentmessage->concept = $record->description;
            $mailcontent = get_string('email_notifications_confirm', 'paygw_bank', $contentmessage);
            $emailuser = new stdClass();
            $emailuser->email = $emailaddress;
            $emailuser->id = -99;
            email_to_user($emailuser, $supportuser, $subject, $mailcontent);
        }
        $transaction->allow_commit();

        return $record;
    }
    public static function files($id): array
    {
        $fs = get_file_storage();
        $files = $fs->get_area_files(\context_system::instance()->id, 'paygw_bank', 'transfer', $id);
        $realfiles=array();
        foreach ($files as $f) {
            if($f->get_filename()!='.') {
                array_push($realfiles, $f);
            }
        }
        return $realfiles;
    }
    public static function get_user($userid)
    {
        global $DB;
        return $DB->get_record('user', ['id' => $userid]);
    }
    public static function deny_pay($id,$canceledbyuser=false): \stdClass
    {
        global $DB, $USER;
        $transaction = $DB->start_delegated_transaction();;
        $record = $DB->get_record('paygw_bank', ['id' => $id]);
        $config = (object) payment_helper::get_gateway_configuration($record->component, $record->paymentarea, $record->itemid, 'bank');
        $payable = payment_helper::get_payable($record->component, $record->paymentarea, $record->itemid);
        $paymentuser=bank_helper::get_user($record->userid);
        $record->timechecked = time();
        $record->status = 'D';
        $record->usercheck = $USER->id;
        $record->canceledbyuser=$canceledbyuser;
        $DB->update_record('paygw_bank', $record);
        $send_email = get_config('paygw_bank', 'senddenmail');
        if ($send_email) {
            $supportuser = core_user::get_support_user();
            $fullname = fullname($paymentuser, true);
            $userlang=$USER->lang;
            $USER->lang=$paymentuser->lang;
          
            $subject = get_string('mail_denied_pay_subject', 'paygw_bank');
            $contentmessage = new stdClass;
            $contentmessage->username = $fullname;
            $contentmessage->useremail = $paymentuser->email;
            $contentmessage->code = $record->code;
            $contentmessage->concept = $record->description;
            $mailcontent = get_string('mail_denied_pay', 'paygw_bank', $contentmessage);
            email_to_user($paymentuser, $supportuser, $subject, $mailcontent);
            $USER->lang=$userlang;
        }
        $transaction->allow_commit();
        return $record;
    }

    public static function get_pending(): array
    {
        global $DB;
        $records = $DB->get_records('paygw_bank', ['status' => 'P']);
        return $records;
    }
    public static function get_user_pending($userid): array
    {
        global $DB;
        $records = $DB->get_records('paygw_bank', ['status' => 'P', 'userid' => $userid]);
        return $records;
    }
    public static function has_openbankentry($itemid, $userid): bool
    {
        global $DB;
        if ($DB->count_records('paygw_bank', ['itemid' => $itemid, 'userid' => $userid, 'status' => 'P']) > 0) {
            return true;
        } else {
            return false;
        }
    }
    public static function create_bankentry($itemid, $userid, $totalamount, $currency, $component, $paymentarea, $description): \stdClass
    {
        global $DB;
        if (bank_helper::has_openbankentry($itemid, $userid)) {
            return null;
        }
        $config = (object) payment_helper::get_gateway_configuration($component, $paymentarea, $itemid, 'bank');
        
        $record = new \stdClass();
        $record->itemid = $itemid;
        $record->component = $component;
        $record->paymentarea = $paymentarea;
        $record->description = $description;
        $record->userid = $userid;
        $record->totalamount = $totalamount;
        $record->currency = $currency;
        $record->code = $record->timemodified = time();
        $record->usercheck = 0;
        $record->status = 'P';
        $record->timecreated = $record->timemodified = time();

        $id = $DB->insert_record('paygw_bank', $record);
        $record->id = $id;
        $codeprefix=$config->codeprefix;
        $record->code = bank_helper::create_code($id, $codeprefix);
        $DB->update_record('paygw_bank', $record);
        $send_email = get_config('paygw_bank', 'sendnewrequestmail');
        $emailaddress=get_config('paygw_bank', 'notificationsaddress');

        if ($send_email) {
            $supportuser = core_user::get_support_user();
            $subject = get_string('email_notifications_subject_new', 'paygw_bank');
            $contentmessage = new stdClass;
            $contentmessage->code = $record->code;
            $contentmessage->concept = $record->description;
            $mailcontent = get_string('email_notifications_new_request', 'paygw_bank', $contentmessage);
            $emailuser = new stdClass();
            $emailuser->email = $emailaddress;
            $emailuser->id = -99;
            email_to_user($emailuser, $supportuser, $subject, $mailcontent);
        }
        return $record;
    }
    public static function create_code($id,$codeprefix=null): string
    {
        if($codeprefix) {
            return $codeprefix . "_" . $id;
        }
        else
        {
            return "code_" . $id;
        }
    }
    public static function get_item_key($component, $paymentarea, $itemid): string
    {
        return $component . "." . $paymentarea . "." . $itemid;
    }
    public static function split_item_key($key): array
    {
        $keyexplode= explode(".", $key);
        return ['component' => $keyexplode[0], 'paymentarea' => $keyexplode[1], 'itemid' => $keyexplode[2]];
    }
    public static function get_pending_item_collections(): array
    {
        global $DB;
        $records = $DB->get_records('paygw_bank', ['status' => 'P']);
        $items = [];
        $itemsstringarray = [];
        foreach ($records as $record) {
            $component = $record->component;
            $paymentarea = $record->paymentarea;
            $itemid = $record->itemid;
            $description = $record->description;
            $key = bank_helper::get_item_key($component, $paymentarea, $itemid);
            if (!in_array($key, $itemsstringarray)) {
                array_push($itemsstringarray, $key);
                array_push($items, ['component' => $component, 'paymentarea' => $paymentarea, 'itemid' => $itemid, 'description' => $description, 'key' => $key]);
            }    
        }
        return $items;
    }
    public static function sendmail($id, $subject, $message): bool
    {
        global $DB;
        $record = $DB->get_record('paygw_bank', ['id' => $id]);
        $paymentuser=bank_helper::get_user($record->userid);
        $supportuser = core_user::get_support_user();
        $fullname = fullname($paymentuser, true);
        $mailcontent = $message;
        email_to_user($paymentuser, $supportuser, $subject, $mailcontent);
        return true;
    }
}
