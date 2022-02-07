<?php
use core_payment\helper;
use paygw_bank\bank_helper as Paygw_bankBank_helper;
use paygw_bank\bank_helper;
require_once(__DIR__ . '/../../../config.php');
require_once('./lib.php');
require_login();
$context = context_system::instance(); // Because we "have no scope".
$PAGE->set_context($context);

$PAGE->set_url('/payment/gateway/bank/manage.php');
$PAGE->set_pagelayout('report');
$pagetitle = 'Manage solicitudes';
$PAGE->set_title($pagetitle);
$PAGE->set_heading($pagetitle);
$confirm = optional_param('confirm', 0, PARAM_INT);
$id = optional_param('id', 0, PARAM_INT);
$action = optional_param('action', '', PARAM_TEXT);

//$config = (object) helper::get_gateway_configuration($component, $paymentarea, $itemid, 'bank');
//$payable = helper::get_payable($component, $paymentarea, $itemid);
//$surcharge = helper::get_gateway_surcharge('bank');
echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('pending_payments', 'paygw_bank'), 2);

if($confirm==1 && $id>0)
{
    if($action=='A')
    {
        bank_helper::aprobe_pay($id);
        $OUTPUT->notification("aprobed");
        \core\notification::info("aprobed");
        
    }
    if($action=='D')
    {
        bank_helper::deny_pay($id);
        \core\notification::info("denied");
        $OUTPUT->notification("denied");
    }
}

$bank_entries= bank_helper::get_pending();
if (!$bank_entries) {
    $match = array();
    echo $OUTPUT->heading(get_string('noentriesfound', 'paygw_bank'));

    $table = NULL;

} else
{
    $table = new html_table();
    $table->head = array(get_string('date'),get_string('code', 'paygw_bank'), get_string('username') ,  get_string('email'),
     get_string('concept', 'paygw_bank'),get_string('total_cost', 'paygw_bank'),get_string('currency'),get_string('hasfiles', 'paygw_bank'),get_string('actions'));
    //$headarray=array(get_string('date'),get_string('code', 'paygw_bank'), get_string('concept', 'paygw_bank'),get_string('amount', 'paygw_bank'),get_string('currency'));
  
    foreach($bank_entries as $bank_entry)
    {
        $config = (object) helper::get_gateway_configuration($bank_entry->component, $bank_entry->paymentarea, $bank_entry->itemid, 'bank');
        $payable = helper::get_payable($bank_entry->component, $bank_entry->paymentarea, $bank_entry->itemid);
        $currency = $payable->get_currency();
        $customer = $DB->get_record('user', array('id' => $bank_entry->userid));
        $fullname = fullname($customer, true);  

        // Add surcharge if there is any.
        $surcharge = helper::get_gateway_surcharge('paypal');
        $amount = helper::get_rounded_cost($payable->get_amount(), $currency, $surcharge);
        $buttonaprobe='<form name="formaprovepay'.$bank_entry->id.'" method="POST">
        <input type="hidden" name="id" value="'.$bank_entry->id.'">
        <input type="hidden" name="action" value="A">
        <input type="hidden" name="confirm" value="1">
        <input type="submit" value="Aprobar"></input>
        </form>';
        $buttondeny='<form name="formaprovepay'.$bank_entry->id.'" method="POST">
        <input type="hidden" name="id" value="'.$bank_entry->id.'">
        <input type="hidden" name="action" value="D">
        <input type="hidden" name="confirm" value="1">
        <input type="submit" value="denegar"></input>
        </form>';
        $files="-";
        $hasfiles=get_string('no');
        if($bank_entry->hasfiles>0)
        {
            $hasfiles='<button type="button" class="btn btn-primary" data-toggle="modal" data-target="#staticBackdrop'.$bank_entry->id.'" id="launchmodal'.$bank_entry->id.'">
            View
          </button>
          <!-- Modal -->
            <div class="modal fade" id="staticBackdrop'.$bank_entry->id.'" aria-labelledby="staticBackdropLabel'.$bank_entry->id.'" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="staticBackdropLabel'.$bank_entry->id.'">'.get_string('files').'</h5>
                    <button type="button" class="btn-close" data-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
              ';
              $fs = get_file_storage();
              $files = $fs->get_area_files(context_system::instance()->id, 'paygw_bank', 'transfer', $bank_entry->id);
              foreach ($files as $f) {
                  // $f is an instance of stored_file
                  $url = moodle_url::make_pluginfile_url($f->get_contextid(), $f->get_component(), $f->get_filearea(), $f->get_itemid(), $f->get_filepath(), $f->get_filename(), false);
                  if(str_ends_with($f->get_filename(),".png")||str_ends_with($f->get_filename(),".jpg")||str_ends_with($f->get_filename(),".gif"))
                  {
                    $hasfiles.= "<img src='$url'><br>";
                  }
                  else {
                    $hasfiles.= '<a href="'.$url.'" target="_blank">.....'.$f->get_filename().'</a><br>';
                  }
              }
              $hasfiles.='
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                </div>
                </div>
            </div>
            </div>
            ';

        }

       
        
     
        $table->data[] = array(date('Y-m-d', $bank_entry->timecreated), $bank_entry->code, $fullname,$customer->email,$bank_entry->description,
            $amount,$currency,$hasfiles,$buttonaprobe.$buttondeny);
    
    }
    echo html_writer::table($table);
}
echo $OUTPUT->footer();
