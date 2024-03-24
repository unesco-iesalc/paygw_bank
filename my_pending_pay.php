<?php
use core_payment\helper;
use paygw_bank\bank_helper;

require_once __DIR__ . '/../../../config.php';
require_once './lib.php';
require_login();

$context = context_system::instance(); // Because we "have no scope".
$PAGE->set_context(context_user::instance($USER->id));
$canuploadfiles=get_config('paygw_bank', 'usercanuploadfiles');
$PAGE->set_url('/payment/gateway/bank/my_pending_pay.php', $params);
$PAGE->set_pagelayout('standard');
$PAGE->set_title(get_string('my_pending_payments', 'paygw_bank'));
$PAGE->navigation->extend_for_user($USER->id);
$PAGE->set_heading(get_string('my_pending_payments', 'paygw_bank'));
$PAGE->navbar->add(get_string('profile'), new moodle_url('/user/profile.php', array('id' => $USER->id)));
$PAGE->navbar->add(get_string('my_pending_payments', 'paygw_bank'));
echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('my_pending_payments', 'paygw_bank'), 2);
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
        $buttongo='<a class="btn btn-primary" href="'.$urlpay.'">'.get_string('go').'</a>';
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
        array_push($dataarray, $buttongo);
        $table->data[]= $dataarray;
    }
    echo html_writer::table($table);
}

echo $OUTPUT->footer();