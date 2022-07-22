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
 * Plugin version and other meta-data are defined here.
 *
 * @package   paygw_bank
 * @copyright UNESCO/IESALC
 * @author    Carlos Vicente Corral <c.vicente@unesco.org>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

function paygw_bank_myprofile_navigation(core_user\output\myprofile\tree $tree, $user, $iscurrentuser, $course)
{
    $url = new moodle_url('/payment/gateway/bank/my_pending_pay.php');
    $category = new core_user\output\myprofile\category('payments', get_string('payments', 'paygw_bank'), null);
    $node = new core_user\output\myprofile\node(
        'payments',
        'my_pending_payments',
        get_string('my_pending_payments', 'paygw_bank'),
        null,
        $url
    );
    $tree->add_category($category);
    $tree->add_node($node);
}

function paygw_bank_pluginfile($course, $cm, $context, $filearea, $args, $forcedownload, array $options = array())
{
    if ($filearea !== 'transfer') {
        return false;
    }

    // Make sure the user is logged in and has access to the module (plugins that are not course modules should leave out the 'cm' part).
    require_login();
    $itemid = array_shift($args); // The first item in the $args array.

    // Use the itemid to retrieve any relevant data records and perform any security checks to see if the
    // user really does have access to the file in question.

    // Extract the filename / filepath from the $args array.
    $filename = array_pop($args);
    if (!$args) {
        $filepath = '/';
    } else {
        $filepath = '/' . implode('/', $args) . '/'; // $args contains elements of the filepath
    }

    // Retrieve the file from the Files API.
    $fs = get_file_storage();
    $file = $fs->get_file($context->id, 'paygw_bank', $filearea, $itemid, $filepath, $filename);
    if (!$file) {
        return false; // The file does not exist.
    }

    // We can now send the file back to the browser - in this case with a cache lifetime of 1 day and no filtering. 
    send_stored_file($file, 86400, 0, $forcedownload, $options);
}

if (!function_exists('str_ends_with')) {
    function str_ends_with($str, $end)
    {
        return (@substr_compare($str, $end, -strlen($end)) == 0);
    }
}
