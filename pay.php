<?php
use core_payment\helper;
use paygw_bank\bank_helper;
use paygw_bank\pay_form;
use paygw_bank\attachtransfer_form;

require_once(__DIR__ . '/../../../config.php');
require_once('./lib.php');
$canuploadfiles=get_config('paygw_bank', 'usercanuploadfiles');
require_login();
$component = required_param('component', PARAM_COMPONENT);
$paymentarea = required_param('paymentarea', PARAM_AREA);
$itemid = required_param('itemid', PARAM_INT);
$description = required_param('description', PARAM_TEXT);
$params = [
    'component' => $component,
    'paymentarea' => $paymentarea,
    'itemid' => $itemid,
    'description' => $description
];
$mform = new pay_form(null,array('confirm'=>1,'component'=>$component, 'paymentarea'=>$paymentarea, 'itemid'=>$itemid,'description'=>$description));
$mform->set_data($params);
$at_form=new attachtransfer_form();
$at_form->set_data($params);
$dataform=$mform->get_data();
$at_dataform=$at_form->get_data();
$confirm =0;
if($dataform!=null)
{
    $component=$dataform->component;
    $paymentarea=$dataform->paymentarea;
    $itemid=$dataform->itemid;
    $description=$dataform->description;
    $confirm=$dataform->confirm;
}
if($at_dataform!=null)
{
    $component=$at_dataform->component;
    $paymentarea=$at_dataform->paymentarea;
    $itemid=$at_dataform->itemid;
    $description=$at_dataform->description;
    $confirm=$at_dataform->confirm;
}

$context = context_system::instance(); // Because we "have no scope".
$PAGE->set_context($context);

$PAGE->set_url('/payment/gateway/bank/pay.php', $params);
$PAGE->set_pagelayout('report');
$pagetitle = $description;
$PAGE->set_title($pagetitle);
$PAGE->set_heading($pagetitle);

$config = (object) helper::get_gateway_configuration($component, $paymentarea, $itemid, 'bank');
$payable = helper::get_payable($component, $paymentarea, $itemid);
$currency = $payable->get_currency();
$bank_entry=null;

// Add surcharge if there is any.
$surcharge = helper::get_gateway_surcharge('bank');
$amount = helper::get_rounded_cost($payable->get_amount(), $currency, $surcharge);
echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('gatewayname', 'paygw_bank'), 2);
echo '<div class="card">';
echo '<div class="card-body">';
echo '<ul class="list-group list-group-flush">';
echo '<li class="list-group-item"><h5 class="card-title">' . get_string('concept', 'paygw_bank') . ':</h5>';
echo '<div>' . $description . '</div>';
echo "</li>";
$aceptform="";
$instructions="";

$instructions=$config->instructionstext['text'] ;

if(bank_helper::has_openbankentry($itemid,$USER->id))
{
    $bank_entry= bank_helper::get_openbankentry($itemid,$USER->id);
    $amount=$bank_entry->totalamount;
    \core\notification::info(get_string('transfer_process_initiated', 'paygw_bank'));
    $confirm=0;

}
else
{
    
    if($confirm!=0)
    {
        $totalamount=$amount;
        $data=$mform->get_data();
        $bank_entry= bank_helper::create_bankentry($itemid,$USER->id,$totalamount,$currency,$component, $paymentarea,$description);
        \core\notification::info(get_string('transfer_process_initiated', 'paygw_bank'));
        $confirm=0;
    }
}
if($surcharge && $surcharge>0 &&$bank_entry==null)
{
    if($surcharge && $surcharge>0 &&$bank_entry==null)
    {
        echo '<li class="list-group-item"><h4 class="card-title">' . get_string('cost', 'paygw_bank') . ':</h4>';
        echo '<div id="price">' . $payable->get_amount(). '</div>';
        echo '</li>';
        echo '<li class="list-group-item"><h4 class="card-title">' . get_string('surcharge', 'core_payment') . ':</h4>';
        echo '<div id="price">' . $surcharge. '%</div>';
        echo '<div id="explanation">' . get_string('surcharge_desc', 'core_payment'). '</div>';
        echo '</li>';
    }
    echo '<li class="list-group-item"><h4 class="card-title">' . get_string('total_cost', 'paygw_bank') . ':</h4>';
echo '<div id="price">' . $amount.' '.$currency. '</div>';
echo '</li>';
}
else
{
    echo '<li class="list-group-item"><h4 class="card-title">' . get_string('total_cost', 'paygw_bank') . ':</h4>';
    echo '<div id="price">' . $amount.' '.$currency. '</div>';
    echo '</li>';
}
if($bank_entry!=null)
{
    echo '<li class="list-group-item"><h4 class="card-title">' . get_string('transfer_code', 'paygw_bank') . ':</h4>';
    echo '<div id="price">' . $bank_entry->code. '</div>';
    echo '</li>';
    $instructions=$config->postinstructionstext['text'] ;
    

}
echo "</ul>";
echo '<div id="bankinstructions">' . $instructions . '</div>';
function my_mktempdir($dir, $prefix='') {
    global $CFG;

    if (substr($dir, -1) != '/') {
        $dir .= '/';
    }

    do {
        $path = $dir.$prefix.mt_rand(0, 9999999);
    } while (file_exists($path));

    check_dir_exists($path);

    return $path;
}
if($confirm==0&& !bank_helper::has_openbankentry($itemid,$USER->id))
{
    $mform->display();
}
else 
{

    if($canuploadfiles)
    {
        $at_form->display();
        $fs = get_file_storage();
        $files = $fs->get_area_files(context_system::instance()->id, 'paygw_bank', 'transfer', $bank_entry->id);
        foreach ($files as $f) {
            // $f is an instance of stored_file
            echo $f->get_filename();
            $url = moodle_url::make_pluginfile_url($f->get_contextid(), $f->get_component(), $f->get_filearea(), $f->get_itemid(), $f->get_filepath(), $f->get_filename(), false);
            if(str_ends_with($f->get_filename(),".png")||str_ends_with($f->get_filename(),".jpg")||str_ends_with($f->get_filename(),".gif"))
            {
                echo "<img src='$url'>";
            }
        }
        
        if($at_form!=null)
        {
            $content = $at_form->get_file_content('userfile');

            $name = $at_form->get_new_filename('userfile');
            if($name)
            {
                $tempdir = my_mktempdir($CFG->tempdir.'/', 'pay');
                $fullpath = $tempdir.'/'.$name;
                $success = $at_form->save_file('userfile', $fullpath, $override);
                $fileinfo = array(
                    'contextid' => context_system::instance()->id,
                    'component' => 'paygw_bank',
                    'filearea' => 'transfer',
                    'filepath' => '/',
                    'filename' =>  $name,
                    'itemid' => $bank_entry->id,
                    'userid'=>$USER->id,
                    'author'=>fullname($USER->true));
                $fs = get_file_storage();
                $fs->create_file_from_pathname($fileinfo, $fullpath);
                bank_helper::check_hasfiles($bank_entry->id);
            }
            
        }
    }
}
echo "</div>";
echo "</div>";
echo $OUTPUT->footer();