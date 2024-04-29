<?php
use core_payment\helper;
use paygw_bank\bank_helper;

require_once __DIR__ . '/../../../config.php';
require_once './lib.php';
require_login();

$context = context_system::instance(); // Because we "have no scope".
$PAGE->set_context(context_user::instance($USER->id));
$canuploadfiles=get_config('paygw_bank', 'usercanuploadfiles');
$allowusercancel=get_config('paygw_bank', 'allowusercancel');
$PAGE->set_url('/payment/gateway/bank/my_pending_pay.php', $params);
$PAGE->set_pagelayout('standard');
$PAGE->set_title(get_string('my_pending_payments', 'paygw_bank'));
$PAGE->navigation->extend_for_user($USER->id);
$PAGE->set_heading(get_string('my_pending_payments', 'paygw_bank'));
$PAGE->navbar->add(get_string('profile'), new moodle_url('/user/profile.php', array('id' => $USER->id)));
$PAGE->navbar->add(get_string('my_pending_payments', 'paygw_bank'));
$action = optional_param('action', '', PARAM_TEXT);
$confirm = optional_param('confirm', 0, PARAM_INT);
$id = optional_param('id', 0, PARAM_INT);
echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('my_pending_payments', 'paygw_bank'), 2);
//if request method is POST
$requestMethod = $_SERVER['REQUEST_METHOD'];

if ($requestMethod == 'POST') {
    if ($confirm == 1 && $id > 0 && $allowusercancel) {
        require_sesskey();
        if ($action == 'D') {
            bank_helper::deny_pay($id, true);
            \core\notification::info(get_string('payment_denied', 'paygw_bank'));
            $OUTPUT->notification(get_string('payment_denied', 'paygw_bank'));
        }
    }
}
$bank_entries= bank_helper::get_user_pending($USER->id);
if (!$bank_entries) {
    $match = array();
    echo $OUTPUT->heading(get_string('noentriesfound', 'paygw_bank'));
    $table = null;

} else
{
    $table = new html_table();
    $canuploadfiles=get_config('paygw_bank', 'usercanuploadfiles');
    $headarray=array(get_string('date'),get_string('code', 'paygw_bank'), get_string('concept', 'paygw_bank'),get_string('total_cost', 'paygw_bank'),get_string('currency'));
    if($canuploadfiles) {
        array_push($headarray, get_string('hasfiles', 'paygw_bank'));
    }
    array_push($headarray, get_string('actions'));
    $table->head=$headarray;
    foreach($bank_entries as $bank_entry)
    {
        $config = (object) helper::get_gateway_configuration($bank_entry->component, $bank_entry->paymentarea, $bank_entry->itemid, 'bank');
        $payable = helper::get_payable($bank_entry->component, $bank_entry->paymentarea, $bank_entry->itemid);
        $currency = $payable->get_currency();
        $customer = $DB->get_record('user', array('id' => $bank_entry->userid));
        $fullname = fullname($customer, true);  

        // Add surcharge if there is any.
        $surcharge = helper::get_gateway_surcharge('paypal');
        $amount = helper::get_cost_as_string($payable->get_amount(), $currency, $surcharge);
        $component = $bank_entry->component;
        $paymentarea = $bank_entry->paymentarea;
        $itemid = $bank_entry->itemid;
        $description = $bank_entry->description;
        $urlpay=new moodle_url('/payment/gateway/bank/pay.php', array('component' => $component,'paymentarea' => $paymentarea,'itemid' => $itemid,'description' => $description));
        $buttongo='<a class="btn btn-primary btn-block" href="'.$urlpay.'">'.get_string('go').'</a>';
        $buttondeny = '<form action="my_pending_pay.php" id="cancel_' . $bank_entry->id . '" method="POST">
        <input type="hidden" name="sesskey" value="' .sesskey(). '">
        <input type="hidden" name="id" value="' . $bank_entry->id . '">
        <input type="hidden" name="action" value="D">
        <input type="hidden" name="confirm" value="1">
        <input class="btn btn-primary btn-block" type="submit" data-modal="confirmation" data-modal-title-str=\'["cancel_process", "paygw_bank"]\'
        data-modal-content-str=\'["are_you_sure_cancel","paygw_bank"]\' data-modal-destination="javascript:document.getElementById(\'cancel_' . $bank_entry->id . '\').submit()" data-modal-yes-button-str=\'["yes", "core"]\' value="' . get_string("cancel_process", "paygw_bank") . '"></input>
        </form>';
        $buttons=$buttongo;
        if($allowusercancel) {
            $buttons=$buttongo.$buttondeny;
        }
        $buttons='<div class="d-grid gap-2">'.$buttons.'</div>';
        $dataarray=array(date('Y-m-d', $bank_entry->timecreated), $bank_entry->code,$bank_entry->description,
        $amount,$currency);
     
        if($canuploadfiles) {
            $hasfiles = get_string('no');
            
            $files=bank_helper::files($bank_entry->id);
            if (count($files)>0) {
                $hasfiles = get_string('yes');
            }
            array_push($dataarray, $hasfiles);
        }
        array_push($dataarray, $buttons);
        $table->data[]= $dataarray;
    }
    echo html_writer::table($table);
}

echo $OUTPUT->footer();