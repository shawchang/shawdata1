<?php

/**
 * File :  application/controllers/DeviceprofilemanagerciController
 * Description : Controller for CI devices
 * Created Date :  2015-11-18
 * Created by :  Shaw Chang
 * E-mail : shaw.chang@hpe.com
 * Date of Change :  2015-11-18
 * Changed by :  Shaw Chang
 * Change log : adding new code comment section.
 * Version no. : 1.0.
 */


function seconds2human($ss) {
    if ($ss == '')
        return 'Not Available';

    $s = $ss % 60;
    $m = floor(($ss % 3600) / 60);
    $h = floor(($ss % 86400) / 3600);
    $d = floor(($ss % 2592000) / 86400);
    $M = floor($ss / 2592000);

    if ($M != 0)
        return "$M months";
    if ($d != 0)
        return "$d days";
    if ($h != 0)
        return "$h hours";
    if ($m != 0)
        return "$m minutes";
    if ($s != 0)
        return "$s seconds";

    return "$ss seconds";
}

function createLink($text, $queue, $type, $sla, $user = "0") {
    if ($text == '')
        $text = '0';
    if ($user == null)
        $user = -1;
    $link = "<a style='margin-left:3px;margin-right:3px;' href='" . SITE_URL . "home/showtickets/dashboard/1/queue_id/$queue/type/$type/sla/$sla/user/$user/month/0'>";
    $link .= $text;
    $link .= "</a>";
    return $link;
}

if (!function_exists('dummyf')) {

    function dummyf($input, $title = "") {
        $html = str_replace("(user)", "<img style='vertical-align:middle' border=0 src='" . SITE_URL . "public/images/user.png' />", $input);
        $html = str_replace("(group)", "<img style='vertical-align:middle' border=0 src='" . SITE_URL . "public/images/group.png' />", $html);
        $html = str_replace("(online)", "<img title='User On-line and Available' style='vertical-align:middle' border=0 src='" . SITE_URL . "public/images/user.png' />", $html);
        $html = str_replace("(offline)", "<img title='User Not On-line or Available' style='vertical-align:middle' border=0 src='" . SITE_URL . "public/images/user_offline.png' />", $html);
        $html = str_replace("(sla1)", "<img title='SLA within 4h'  style='vertical-align:middle' border=0 src='" . SITE_URL . "public/images/sla1.png' />", $html);
        $html = str_replace("(sla2)", "<img title='SLA more then 4h' style='vertical-align:middle' border=0 src='" . SITE_URL . "public/images/sla2.png' />", $html);
        $html = str_replace("(sla3)", "<img title='SLA more then 8h' style='vertical-align:middle' border=0 src='" . SITE_URL . "public/images/sla3.png' />", $html);
        $html = str_replace("(oncall)", "<img title='SLA more then 8h' style='vertical-align:middle' border=0 src='" . SITE_URL . "public/images/oncall.png' />", $html);
        $html = str_replace("(triage)", "<img title='SLA more then 8h' style='vertical-align:middle' border=0 src='" . SITE_URL . "public/images/triage.png' />", $html);

        if ($title != "")
            return "<div class='title_divs' title='$title' style='overflow:hidden;max-height:17px;font-size:10px;white-space:nowrap;height:17px'>$html</div>";
        return "<div class='title_divs' title='$input' style='overflow:hidden;max-height:17px;font-size:10px;white-space:nowrap;height:17px'>$html</div>";
    }

}

if (!function_exists('ddd')) {

    function ddd($input) {
        var_dump($input);
        die();
    }

}

class DeviceprofilemanagerciController extends Zend_Controller_Action {

    private $SLA1 = "INTERVAL 4 HOUR";
    private $SLA2 = "INTERVAL 6 HOUR";
    private $_db;
    private $userID;
    private $orgID;
    private $client_id;
    private $vertica;

    public function init() {
        $this->_helper->layout->setLayout('netsrm');
        $this->_db = Zend_Registry::get('db');

        $this->userID = 0;
        $auth = Zend_Auth::getInstance();
        if ($auth->hasIdentity()) {
            $this->userID = $auth->getIdentity()->user_id;
            $this->client_id = $auth->getIdentity()->clients_id;
        }


        $SSINamespace = new Zend_Session_Namespace('SSINamespace');
        $this->view->canWrite = ((intval($SSINamespace->actualPermission) & 3) == 3);
        ///////////////////////////////////////////////// Vertica-shaw
        $this->vertica = new Application_Model_Verticamodel();
        $this->vertica = $vertica;
    }

    private function vertica_log_new($array = array()) {
      ///  ddd ($array);
        $this->vertica = new Application_Model_Verticamodel();
        $this->vertica->logActivity($array);
    }

    private function vertica_log($activity = 'test-1234567', $systemid, $taskid, $taskdesc, $extsystemid, $actionid, $hpnataskid, $hpnasessionid, $incomingcall, $starttimestamp, $endtimestamp, $initiationtype, $initiatedby, $status, $result, $resultdetail, $targetci) {
        $data['activity'] = substr($activity, 0, 44);
        if (!empty($systemid)) {
            $data['systemid'] = $systemid;
        }
        if (!empty($taskid)) {
            $data['taskid'] = $taskid;
        }
        if (!empty($taskdesc)) {
            $data['taskdesc'] = substr($taskdesc, 0, 249);
        }
        if (!empty($extsystemid)) {
            $data['extsystemid'] = $extsystemid;
        }
        if (!empty($actionid)) {
            $data['actionid'] = $actionid;
        }
        if (!empty($hpnataskid)) {
            $data['hpnataskid'] = $hpnataskid;
        }
        if (!empty($hpnasessionid)) {
            $data['hpnasessionid'] = $hpnasessionid;
        }
        if (!empty($incomingcall)) {
            $data['incomingcall'] = substr($incomingcall, 0, 44);
        }
        $data['entrydatetime'] = date('Y-m-d H:i:s', time());
        if (!empty($starttimestamp)) {
            $data['starttimestamp'] = $starttimestamp;
        }
        if (!empty($endtimestamp)) {
            $data['endtimestamp'] = $endtimestamp;
        }
        if (!empty($initiationtype)) {
            $data['initiationtype'] = substr($initiationtype, 0, 44);
        }
        if (!empty($initiatedby)) {
            $data['initiatedby'] = substr($initiatedby, 0, 44);
        }
        if (!empty($status)) {
            $data['status'] = substr($status, 0, 44);
        }
        if (!empty($result)) {
            $data['result'] = substr($result, 0, 44);
        }
        if (!empty($resultdetail)) {
            $data['resultdetail'] = substr($resultdetail, 0, 249);
        }
        if (!empty($targetci)) {
            $data['targetci'] = substr($targetci, 0, 44);
        }
        $this->vertica->logActivity($data);
    }

    public function indexAction() {
        
    }

    public function devicetypesAction() {
        $this->_helper->layout->disableLayout();
        $device_manager = new Application_Model_Deviceprofilemanager();
        $device_types = $device_manager->getdeviceTypes();
        $this->view->device_types = $device_types;
    }

    public function temp1Action() {
        echo ('hello');
        exit();
    }

    public function popupAction() {

        echo('Shaw here');
        $this->_helper->layout->disableLayout();
    }

    public function deviceciinstanceAction() {


        $this->_helper->layout->disableLayout();
        $request = $this->getRequest();
        $accounttype_id = $request->getParam('accounttype_id');
        $this->view->accounttype_id = $accounttype_id;

        $deviceciinstance_mgm = new Application_Model_Devicecimanager();
        $deviceciinstance_result = $deviceciinstance_mgm->getdeviceciinstance($accounttype_id);
        $account_details = $deviceciinstance_mgm->getaccountTypes($this->userID, $accounttype_id);

        $this->view->account_details = $account_details;
        $this->view->deviceciinstance_result = $deviceciinstance_result;
    }

    // deviceciinstanceviewAction's grid builder
    function manage_function1($deviceprofile_id, $action_id, $type, $devicetype_id, $vendor_id, $osv_id, $os_id) {

        $device_manager = new Application_Model_Devicecimanager();
        if ($type == 'CommonAction') {
            $deviceaction_details = $device_manager->Getdeviceactionid($deviceprofile_id, $action_id);
            $deviceaction_id = $deviceaction_details['id'];
        } else {
            $deviceaction_id = $action_id;
        }

        $edit = "<a href='" . SITE_URL . "automationactionsmanager/deviceactionformedit/devicetype_id/$devicetype_id/devicevendor_id/$vendor_id/codebase_id/$osv_id/deviceos_id/$os_id/deviceaction_id/$deviceaction_id/'>"
                . "<img style='vertical-align:middle' border=0 src='" . SITE_URL . "public/images/NetSRM%20Icons/Launch%20Page%20Icons/Critical%20Icons/ticket.png' alt='View' /></a>&nbsp;&nbsp;
            
             <a  href='#'><img style='vertical-align:middle' border=0 src='" . SITE_URL . "public/images/Energy_RGB_gray.png' alt='Edit' /></a>&nbsp;&nbsp;";

        return "<div style='width:100%;display:table;text-align: left'>" . $edit . "</div>";
    }

    function manage_function11($deviceprofile_id, $action_id, $type, $devicetype_id, $vendor_id, $osv_id, $os_id) {
   //     ddd($deviceprofile_id); // =7
        $device_manager = new Application_Model_Devicecimanager();
        if ($type == 'CommonAction') {
            $deviceaction_details = $device_manager->Getdeviceactionid($deviceprofile_id, $action_id);
     //       ddd($deviceaction_details);
            /*
             * array (size=3)
                'id' => string '247' (length=3)
                'commonactionid' => string '3' (length=1)
                'deviceprofileid' => string '12' (length=2)
             */
            $deviceaction_id = $deviceaction_details['id'];
     //     ddd($deviceaction_details['deviceprofileid']); GOOD =16
            
            $this->view->deviceprofile_id = $deviceaction_details['deviceprofileid'];
    //      $this->view->deviceciinstance_result = $deviceciinstance_result;
        } else {
            $this->view->deviceprofile_id = $deviceaction_details['deviceprofileid'];
            $deviceaction_id = $action_id;
        }

        $edit = "<a class='iframe456' act-id='" . $deviceaction_id . "' href=''>"
                . "<img style='vertical-align:middle' border=0 src='" . SITE_URL . "public/images/NetSRM%20Icons/Launch%20Page%20Icons/Critical%20Icons/ticket.png' alt='View' /></a>&nbsp;&nbsp;
            
             <a  href='#' class='iframe123' act-id='" . $deviceaction_id . "'>
             <img style='vertical-align:middle' border=0 src='" . SITE_URL . "public/images/Energy_RGB_gray.png' alt='Edit' /></a>&nbsp;&nbsp;";
       // ddd($edit);
        /*
         * string '<a class='iframe456' act-id='264' href=''><img test123 style='vertical-align:middle' border=0 src='http://localhost:8080/si2-nascent/public/images/NetSRM%20Icons/Launch%20Page%20Icons/Critical%20Icons/ticket.png' alt='View' /></a>&nbsp;&nbsp;
            
             <a  href='#'><img test456 style='vertical-align:middle' border=0 src='http://localhost:8080/si2-nascent/public/images/Energy_RGB_gray.png' alt='Edit' /></a>&nbsp;&nbsp;' (length=437)
         * 
         */
        return "<div style='width:100%;display:table;text-align: left'>" . $edit . "</div>";
    }

    function manage_function2($deviceconfigid) {
        $edit = "<a href='#' configid='" . $deviceconfigid . "' class='show_full_config'>"
                . "<img style='vertical-align:middle' border=0 src='" . SITE_URL . "public/images/NetSRM%20Icons/Launch%20Page%20Icons/Critical%20Icons/ticket.png' alt='View' /></a>&nbsp;&nbsp;
                    
		
                <a href='#' id='config_parser'><img style='vertical-align:middle' border=0 src='" . SITE_URL . "public/images/Automation_RGB_gray.png' alt='Edit' /></a>&nbsp;&nbsp;";

        return "<div style='width:100%;display:table;text-align: left'>" . $edit . "</div>";
    }

    public function automationactivitylogAction() {
        $this->_helper->layout->disableLayout();
        $SSINamespace = new Zend_Session_Namespace('SSINamespace');
        $grid = $this->grid();
        $request = $this->getRequest();
        $taskid = $request->getParam('taskid');

        $targetci = $request->getParam('targetci');
        $dn = $request->getParam('dn');

        $device_manager = new Application_Model_Devicecimanager();
        $content = $device_manager->getautomationactivitylog($taskid);

        $source = new Bvb_Grid_Source_Array($content);
        $grid = $this->grid();
        $grid->setSource($source);
        $grid->setNoFilters(true);

        $grid->updateColumn('id', array('hidden' => true));
        $grid->updateColumn('systemid', array('hidden' => true));
        $grid->updateColumn('extsystemid', array('hidden' => true));
        $grid->updateColumn('actionid', array('hidden' => true));
        $grid->updateColumn('hpnataskid', array('hidden' => true));
        $grid->updateColumn('hpnasessionid', array('hidden' => true));
        $grid->updateColumn('status', array('hidden' => true));
        $grid->updateColumn('resultdetail', array('callback' => array('function' => array($this, 'description_function'),
                'params' => array('{{id}}', '{{resultdetail}}'))
        ));

        $this->view->targetci = $targetci;

        $this->view->taskid = $taskid;
        $this->view->taskdesc = $content[0]['taskdesc'];

        $this->view->dn = $dn;
        $this->view->pages3 = $grid->deploy();
    }

   public function cinotesAction() {
        $this->_helper->layout->disableLayout();
        $identity = Zend_Auth::getInstance()->getIdentity();
        $userid = $identity->user_id; // GOOD
        $request = $this->getRequest();
        $device_id = $request->getParam('device_id'); // GOOD
        $note1 = $request->getParam('note');
        $id=''; //good
        $Timestamp=date('Y-m-d H:i:s', time()); //good
        $deviceid = $device_id; //good
        $note=$note1;  // GOOD GOOD GOOD
   
             
        $device_manager = new Application_Model_Devicecimanager();
        $content = $device_manager->cinotes($id, $userid, $Timestamp, $deviceid, $note);
       
} 

	public function cinotesupdateAction() {
        $this->_helper->layout->disableLayout();
        $request = $this->getRequest();
        $this->_helper->viewRenderer->setNoRender(true);
        $id = $request->getParam('id'); // GOOD
        $note = $request->getParam('note');
        $note = (string)$note;
       
        $device_manager = new Application_Model_Devicecimanager();
        $content = $device_manager->cinotesupdate($id, $note);
		echo $content;
    } 


public function cinoteseditAction() {
        $this->_helper->layout->disableLayout();
        $request = $this->getRequest();
        
        $device_id = $request->getParam('device_id'); // GOOD
        $id = $request->getParam('id'); // GOOD
        
        $deviceid = $device_id; //good
 
        $device_manager = new Application_Model_Devicecimanager();
        $content = $device_manager->cinotesedit($deviceid, $id);
        //array (size=2)
        //  'note' => string 'hello 3' (length=7)  // good
        //  'id' => string '141' (length=3)   //good
        // SVN test
        
        $this->view->note = $content[note];
        $this->view->id = $content[id];
  
}

    public function ciactivityhistoryAction() {
        $this->_helper->layout->disableLayout();

        $request = $this->getRequest();
        $ip = $request->getParam('ip');
        $dn = $request->getParam('dn');

        $this->view->ip = $ip;
        $this->view->dn = $dn;
        $device_manager = new Application_Model_Devicecimanager();
        $content = $device_manager->getciactivityhistory($ip);

        if (!empty($content)) {
            $source = new Bvb_Grid_Source_Array($content);
            $grid = $this->grid();
            $grid->setSource($source);
            $grid->setNoFilters(true);
            $manage = new Bvb_Grid_Extra_Column();
            $manage->position('left')->name('Manage');
            $manage->callback(array(
                'function' => array($this, 'manage_function3'),
                'params' => array('{{taskid}}', '{{targetci}}', '{{dn}}')
            ));

            $grid->updateColumn('resultdetail', array('callback' => array('function' => array($this, 'description_function'),
                    'params' => array('{{id}}', '{{resultdetail}}', '{{taskid}}', '{{taskdesc}}'))
            ));

            $grid->addExtraColumns($manage);
            $grid->updateColumn('id', array('hidden' => true));
            $grid->updateColumn('status', array('hidden' => true));
            $this->view->pages = $grid->deploy();
        }
    }

    function description_function($log_id, $result_details, $task_id, $task_desc) {

        $device_manager = new Application_Model_Devicecimanager();
        $check = $device_manager->isBase64(trim($result_details));

        if ($check) {
            $edit = '<a href="#" class="rdetailspopup" log-id="' . $log_id . '"  task-id="' . $task_id . '" task-desc="' . $task_desc . '" style="color : #007DBA" onMouseOut="this.style.color=\'#007DBA\'" onMouseOver="this.style.color=\'#FFFFFF\'">Show details</a>';
        } elseif (strlen($result_details) > 50) {
            $edit = '<a href="#" log-id="' . $log_id . '"  task-id="' . $task_id . '" task-desc="' . $task_desc . '"  class="rdetailspopup" style="color : #007DBA" onMouseOut="this.style.color=\'#007DBA\'" onMouseOver="this.style.color=\'#FFFFFF\'">' . substr($result_details, 0, 50) . '...' . '</a>'

            ;
        } else {
            $edit = $result_details;
        }

        return $edit;
    }

    function manage_function3($taskid, $targetci, $dn) {
        $edit = "<a href='" . SITE_URL . "Deviceprofilemanagerci/automationactivitylog/taskid/$taskid/targetci/$targetci/dn/$dn'>"
                . "<img style='vertical-align:middle' border=0 src='" . SITE_URL . "public/images/NetSRM%20Icons/Launch%20Page%20Icons/Critical%20Icons/ticket.png' alt='View' /></a>&nbsp;&nbsp";
        return "<div style='width:100%;display:table;text-align: left'>" . $edit . "</div>";
    }

    public function activityresultdetailsAction() {
        $this->_helper->layout->disableLayout();
        $request = $this->getRequest();
        $logid = $request->getParam('logid');
        $dn = $request->getParam('dn');
        $taskid = $request->getParam('taskid');
        $taskdesc = $request->getParam('taskdesc');
        $ip = $request->getParam('ip');
        $device_manager = new Application_Model_Devicecimanager();
        $log_details = $device_manager->getactivitylogbyid($logid);

        $this->view->dn = $dn;
        $this->view->ip = $ip;
        $this->view->taskid = $taskid;
        $this->view->taskdesc = $taskdesc;

        $check = $device_manager->isBase64(trim($log_details['resultdetail']));
        if ($check) {
            $this->view->resultdetails = base64_decode(trim($log_details['resultdetail']), true);
        } else {
            $this->view->resultdetails = trim($log_details['resultdetail']);
        }
    }

    public function gridiframeAction() {
        $this->_helper->layout->disableLayout();
        $request = $this->getRequest();
        $device_id = $request->getParam('device_id');

        $this->view->device_id = $device_id;

        $this->view->device_id = $device_id;
        $device_manager = new Application_Model_Devicecimanager();

        $grid = $this->grid();
        $grid->setPaginationInterval(array(10 => 10, 25 => 25, 50 => 50, 100 => 100));
        $grid->setDefaultEscapeFunction('dummyf');

        $content = $device_manager->getDeviceCIDetails($device_id);
    
        $this->view->devicetype_id = $content[0]['devicetype_id'];
        $this->view->vendor_id = $content[0]['vendor_id'];
        
        ($this->view->dp_id = $content[0]['dp_id']);
        $this->view->osv_id = $content[0]['osv_id'];
        $this->view->os_id = $content[0]['os_id'];

        $CommonActions = $device_manager->getCommonActions($content[0]['ipaddress'], $content[0]['accounttype_id']);

        if (!empty($CommonActions)) {
            $source11 = new Bvb_Grid_Source_Array($CommonActions);
     
            $this->view->CommonActions = $CommonActions;
            $grid->setSource($source11);
            $grid->updateColumn('id', array('hidden' => true));
            $grid->updateColumn('deviceprofileid', array('hidden' => true));

            $manage = new Bvb_Grid_Extra_Column();
            $manage->position('left')->name('Manage');
            $manage->callback(array(
                'function' => array($this, 'manage_function11'),
                'params' => array('{{deviceprofileid}}', '{{id}}', '{{TYPE}}', '{{devicetype_id}}', '{{vendor_id}}', 37, '{{os_id}}')
                    )
            );

            $grid->addExtraColumns($manage);
            $this->view->action_id = $action_id;
            $this->view->type = $type;
            $this->view->pages = $grid->deploy();
        }

        $grid = $this->grid();
        $getActions1 = $device_manager->getActions1($content[0]['ipaddress'], $content[0]['accounttype_id']);
     
        $this->view->deviceaction_id = $getActions1[0]['deviceaction_id'];

        
        // strip html tags <p></p> for description
        if (!empty($getActions1)) {
            foreach ($getActions1 as $k => $action) {
                $getActions1[$k]['deviceaction_description'] = str_replace(array('<p>', '</p>'), '', $action['deviceaction_description']);
                $string = $getActions1[$k]['deviceaction_description'];

                if (strlen($string) > 10) {
                    $string = wordwrap($string, 5);
                    $string = substr($string, 0, strpos($string, "\n"));
                    $getActions1[$k]['deviceaction_description'] = $string;
                }
            }
        }

        $Configuration = $device_manager->getDeviceConfigurations($device_id);
        $this->view->configurations = $Configuration; // array

        if (count($Configuration) > 0) {
            $grid = $this->grid();
            $source111 = new Bvb_Grid_Source_Array($Configuration);
            $grid->setSource($source111);

            $manage = new Bvb_Grid_Extra_Column();
            $manage->position('left')->name('Manage');
            $manage->callback(array(
                'function' => array($this, 'manage_function2'),
                'params' => array('{{id}}')
                    )
            );
            $grid->addExtraColumns($manage);
            $grid->updateColumn('id', array('hidden' => true));
            $grid->updateColumn('text', array('hidden' => true));
            $this->view->pages1 = $grid->deploy();
        }
    }

    public function gridiframerealtimeAction() {
        $this->_helper->layout->disableLayout();

        $request = $this->getRequest();
        $device_id = $request->getParam('device_id');
        $this->view->device_id = $device_id;

        $this->view->device_id = $device_id;
        $device_manager = new Application_Model_Devicecimanager();

        $grid = $this->grid();
        $grid->setDefaultEscapeFunction('dummyf');
        $content = $device_manager->getDeviceCIDetails($device_id);
        $this->view->dactions = $dactions;
        $this->view->content = $content;



        $CommonActions = $device_manager->getCommonActions($content[0]['ipaddress'], $content[0]['accounttype_id']);

        if (!empty($CommonActions)) {
            $source11 = new Bvb_Grid_Source_Array($CommonActions);
            $this->view->CommonActions = $CommonActions;
            $grid->setSource($source11);

            $manage = new Bvb_Grid_Extra_Column();
            $manage->position('left')->name('Manage');
            $manage->callback(array(
                'function' => array($this, 'manage_function1'),
                'params' => array('{{deviceprofileid}}', '{{id}}', '{{TYPE}}', $content[0]['devicetype_id'],
                    $content[0]['vendor_id'],
                    37,
                    $content[0]['os_id'])
                    )
            );

            $grid->addExtraColumns($manage);

            $this->view->pages = $grid->deploy();
        }
        $grid = $this->grid();
        $getActions1 = $device_manager->getActions1($content[0]['ipaddress'], $content[0]['accounttype_id']);

        if (!empty($getActions1)) {
            foreach ($getActions1 as $k => $action) {
                $getActions1[$k]['deviceaction_description'] = str_replace(array('<p>', '</p>'), '', $action['deviceaction_description']);

                $string = $getActions1[$k]['deviceaction_description'];

                if (strlen($string) > 10) {
                    $string = wordwrap($string, 5);
                    $string = substr($string, 0, strpos($string, "\n"));
                    $getActions1[$k]['deviceaction_description'] = $string;
                }
            }
        }

        $Configuration = $device_manager->getDeviceConfigurations($device_id);
        $this->view->configurations = $Configuration; // array

        if (count($Configuration) > 0) {
            $grid = $this->grid();
            $source111 = new Bvb_Grid_Source_Array($Configuration);
            $grid->setSource($source111);
            $grid->setPaginationInterval(array(10 => 10, 20 => 20, 50 => 50, 100 => 100));

            $manage = new Bvb_Grid_Extra_Column();
            $manage->position('left')->name('Manage');
            $manage->callback(array(
                'function' => array($this, 'manage_function2'),
                'params' => array('{{id}}')
                    )
            );
            $grid->addExtraColumns($manage);
            $grid->updateColumn('id', array('hidden' => true));
            $grid->updateColumn('text', array('hidden' => true));

            $this->view->pages1 = $grid->deploy();
        }
        // ===========================================================================================     
    }

    public function deviceciinstanceviewAction() {
        $this->_helper->layout->disableLayout();

        $request = $this->getRequest();
        $device_id = $request->getParam('device_id');
        $this->view->device_id = $device_id;

        $this->view->device_id = $device_id;
        $device_manager = new Application_Model_Devicecimanager();

        $grid = $this->grid();
        $grid->setDefaultEscapeFunction('dummyf');

        $content = $device_manager->getDeviceCIDetails($device_id);
        $this->view->dactions = $dactions;
        $this->view->content = $content;
        $CommonActions = $device_manager->getCommonActions($content[0]['ipaddress'], $content[0]['accounttype_id']);
        if (!empty($CommonActions)) {
            $source11 = new Bvb_Grid_Source_Array($CommonActions);
            $this->view->CommonActions = $CommonActions;
            $grid->setSource($source11);
            $manage = new Bvb_Grid_Extra_Column();
            $manage->position('left')->name('Manage');
            $manage->callback(array(
                'function' => array($this, 'manage_function1'),
                'params' => array('{{deviceprofileid}}', '{{id}}', '{{TYPE}}', $content[0]['devicetype_id'],
                    $content[0]['vendor_id'],
                    34,
                    $content[0]['os_id'])
                    )
            );

            $grid->addExtraColumns($manage);
            $this->view->pages = $grid->deploy();
        }
        $grid = $this->grid();
        $getActions1 = $device_manager->getActions1($content[0]['ipaddress'], $content[0]['accounttype_id']);
        if (!empty($getActions1)) {
            foreach ($getActions1 as $k => $action) {
                $getActions1[$k]['deviceaction_description'] = str_replace(array('<p>', '</p>'), '', $action['deviceaction_description']);


                $string = $getActions1[$k]['deviceaction_description'];
                if (strlen($string) > 10) {
                    $string = wordwrap($string, 5);
                    $string = substr($string, 0, strpos($string, "\n"));
                    $getActions1[$k]['deviceaction_description'] = $string;
                }
            }
        }


        $Configuration = $device_manager->getDeviceConfigurations($device_id);
        $this->view->configurations = $Configuration; // array
        if (count($Configuration) > 0) {
            $grid = $this->grid();
            $source111 = new Bvb_Grid_Source_Array($Configuration);
            $grid->setSource($source111);

            $manage = new Bvb_Grid_Extra_Column();
            $manage->position('left')->name('Manage');
            $manage->callback(array(
                'function' => array($this, 'manage_function2'),
                'params' => array('{{id}}')
                    )
            );
            $grid->addExtraColumns($manage);
            $grid->updateColumn('id', array('hidden' => true));
            $grid->updateColumn('text', array('hidden' => true));

            $this->view->pages1 = $grid->deploy();
        }
           
    }

    public function deviceciinstanceeditAction() {
        $this->_helper->layout->disableLayout();
        $request = $this->getRequest();
        $device_id = $request->getParam('device_id');
        $this->view->device_id = $device_id;
        $device_manager = new Application_Model_Devicecimanager();
        $dp_manager = new Application_Model_Deviceprofilemanager();
        $content = $device_manager->getDeviceCIDetails($device_id);
        $dp_list = $dp_manager->getDeviceProfileListB();

        $this->view->dp_list = $dp_list;
        $this->view->content = $content;

        $deviceciinstance_result = 0;
        $this->view->deviceciinstance_result = $deviceciinstance_result;

        $asp123 = new Application_Model_Devicecimanager();
        $aspinstance = $asp123->getASPinstance();

        $this->view->aspinstance = $aspinstance;
        $request = $this->getRequest();
        $device_id = $request->getParam('device_id');
        $Status1 = $request->getParam('Status1');
        $Timestamp = $request->getParam('Timestamp');
        $Status2 = $request->getParam('Status2');
        $fqdn = $request->getParam('fqdn');
        $Text = $request->getParam('Text');


        $this->view->device_id = $device_id;
        $this->view->content[0]['test_device'] = $content[0]['test_device'];
        $this->view->device_id = $device_id;
        $device_manager = new Application_Model_Devicecimanager();
        $grid = $this->grid();
        $grid->setDefaultEscapeFunction('dummyf');
        $content = $device_manager->getDeviceCIDetails($device_id);
        $this->view->dactions = $dactions;
        $this->view->content = $content;
        $CommonActions = $device_manager->getCommonActions($content[0]['ipaddress'], $content[0]['accounttype_id']);

        if (!empty($CommonActions)) {
            $source11 = new Bvb_Grid_Source_Array($CommonActions);
            $this->view->CommonActions = $CommonActions;
            $grid->setSource($source11);

            $manage = new Bvb_Grid_Extra_Column();
            $manage->position('left')->name('Manage');
            $manage->callback(array(
                'function' => array($this, 'manage_function1'),
                'params' => array('{{deviceprofileid}}', '{{id}}', '{{TYPE}}', $content[0]['devicetype_id'],
                    $content[0]['vendor_id'],
                    35,
                    $content[0]['os_id'])
                    )
            );

            $grid->addExtraColumns($manage);

            $this->view->pages = $grid->deploy();
        }

        $grid = $this->grid();
        $getActions1 = $device_manager->getActions1($content[0]['ipaddress'], $content[0]['accounttype_id']);

        if (!empty($getActions1)) {
            foreach ($getActions1 as $k => $action) {
                $getActions1[$k]['deviceaction_description'] = str_replace(array('<p>', '</p>'), '', $action['deviceaction_description']);

                $string = $getActions1[$k]['deviceaction_description'];

                if (strlen($string) > 10) {
                    $string = wordwrap($string, 5);
                    $string = substr($string, 0, strpos($string, "\n"));
                    $getActions1[$k]['deviceaction_description'] = $string;
                }
            }
        }

        $Configuration = $device_manager->getDeviceConfigurations($device_id);

        $this->view->configurations = $Configuration; // array

        if (count($Configuration) > 0) {
            $grid = $this->grid();

            $source111 = new Bvb_Grid_Source_Array($Configuration);

            $grid->setSource($source111);

            $manage = new Bvb_Grid_Extra_Column();

            $manage->position('left')->name('Manage');
            $manage->callback(array(
                'function' => array($this, 'manage_function2'),
                'params' => array('{{id}}')
                    )
            );

            $grid->addExtraColumns($manage);
            $grid->updateColumn('id', array('hidden' => true));
            $grid->updateColumn('text', array('hidden' => true));
            $this->view->pages1 = $grid->deploy();
        }
    }

    public function testpageAction() {
        $this->_helper->layout->disableLayout();
        $request = $this->getRequest();
        $ip = $request->getParam('ipaddress');
        $fqdn = $request->getParam('devicename');
        $deviceid = $request->getParam('device_id');
        $Status1 = $request->getParam('status1');
        $identity = Zend_Auth::getInstance()->getIdentity();
        $user_name = $identity->user_name;

        $HPNA_manager = new Application_Model_Extsyshpna();

        $HPNA_manager_action_result = $HPNA_manager->showdeviceconfig($ip, $fqdn);
        $results = json_decode($HPNA_manager_action_result);

        foreach ($results as $result => $result_value) {
            // $result_value = json_decode($result_value);
            $Status1 = $result_value->Status;
            $Timestamp = $result_value->Timestamp;
            $Status2 = $result_value->ExtResult->Status;

            $Text = $result_value->ExtResult->Text;
        }

        $device_manager = new Application_Model_Devicecimanager();
        $Timestamp = date('Y-m-d H:i:s', time());

        $device_manager->RealConfiguration($Status1, $Timestamp, $Status2, $fqdn, $Text, $deviceid);
        $this->view->devicename = $fqdn;
        $this->view->ipaddress = $ip;
        $this->view->timestamp = $Timestamp;
        $this->view->text = $Text;

        $data = array();
        $data['activity'] = 'Realtime Configuration';
        $data['systemid'] = 14;
        $data['taskid'] = time() . "." . mt_rand();
        $data['taskdesc'] = 'Realtime Configuration';
        $data['extsystemid'] = '';
        $data['actionid'] = '';
        $data['hpnataskid'] = '';
        $data['hpnasessionid'] = '';
        $data['incomingcall'] = '';
        $data['entrydatetime'] = date('Y-m-d H:i:s', time());
        $data['starttimestamp'] = $Timestamp;
        $data['endtimestamp'] = date('Y-m-d H:i:s', time());
        $data['initiationtype'] = '';
        $data['initiatedby'] = $user_name;
        $data['status'] = 'SUCCESS';
        $data['result'] = 'SUCCESS';
        $data['resultdetail'] = 'Realtime Configuration saved successfully';
        $data['targetci'] = $ip;
        $this->vertica_log_new($data);
        // END VERTICA LOGGING //
    }

    //   Action to get list of vendors having device profiles, with perticular device types
    public function devicevendorsAction() {
        $request = $this->getRequest();
        $devicetype_id = $request->getParam('devicetype_id');
        $this->_helper->layout->disableLayout();
        $this->_helper->viewRenderer->setNoRender(true);
        $device_manager = new Application_Model_Deviceprofilemanager();
        $device_type = $device_manager->getdeviceTypes($devicetype_id);
        $device_vendors = $device_manager->getdeviceVendors($devicetype_id);
        echo Zend_Json::encode($device_vendors);
    }

    /**
      Action to List OS Versions based on Device Type, Vendor ID
     * */
    public function devicecodebaseAction() {
        $request = $this->getRequest();
        $devicetype_id = $request->getParam('devicetype_id');
        $devicevendor_id = $request->getParam('devicevendor_id');
        $this->_helper->layout->disableLayout();
        $this->_helper->viewRenderer->setNoRender(true);
        $device_manager = new Application_Model_Deviceprofilemanager();
        $device_osversions = $device_manager->getdeviceOSVersions($devicevendor_id, $devicetype_id);
        echo Zend_Json::encode($device_osversions);
    }

    /**
      Action to get list of Device profiles based on Device type, Vendor, OS version
     * */
    public function getdplistAction() {
        $request = $this->getRequest();
        $devicetype_id = $request->getParam('devicetype_id');
        $devicevendor_id = $request->getParam('devicevendor_id');
        $deviceosversion_id = $request->getParam('deviceosversion_id');
        $this->_helper->layout->disableLayout();
        $this->_helper->viewRenderer->setNoRender(true);
        $device_manager = new Application_Model_Deviceprofilemanager();
        $dp_list = $device_manager->getDeviceProfileListB($devicetype_id, $devicevendor_id, $deviceosversion_id);
        echo Zend_Json::encode($dp_list);
    }

    public function profileselectionAction() {
        $this->_helper->layout->disableLayout();
        $device_manager = new Application_Model_Deviceprofilemanager();

        $device_types = $device_manager->getdeviceTypes();
        $vendors = $device_manager->getvendors();
        $dp_list = $device_manager->getDeviceProfileListB();

        $this->view->vendors = $vendors;
        $this->view->dp_list = $dp_list;
        $this->view->device_types = $device_types;
        $this->view->device_profile_details;
    }
    
    public function cinote1Action() {
        $this->_helper->layout->disableLayout();
        $request = $this->getRequest();
        $device_id = $request->getParam('device_id');
        $device_manager = new Application_Model_Devicecimanager();
        $note2 = $device_manager->getNotes($device_id);
        $this->view->device_id = $device_id;
        $this->view->note2 = $note2;
    }
    
    
    

    public function accounttypesAction() {
        $this->_helper->layout->disableLayout();
        $device_manager = new Application_Model_Devicecimanager();
        $device_types = $device_manager->getaccountTypes($this->userID);
        $this->view->device_types = $device_types;
    }

    public function accounttypeslistAction() {
        $this->_helper->layout->disableLayout();
        $device_manager = new Application_Model_Devicecimanager();
        $accounttypeslist = $device_manager->accounttypeslist();
        $this->view->account_types = $accounttypeslist[0];
    }

    public function adddeviceprofileAction() {
        $this->_helper->layout->disableLayout();
        $this->_helper->viewRenderer->setNoRender(true);
        $profile_manager = new Application_Model_Deviceprofilemanager();
        $request = $this->getRequest();
        $params = $request->getParams();
        $resp = $profile_manager->AddDeviceProfile($params);
        $this->_redirect('Deviceprofilemanager/manage/devicetype_id/' . $params['devicetype'] . '/devicevendor_id/' . $params['vendor'] . '/deviceosv_id/' . $params['deviceosversion']);
    }

    public function changedeviceprofileAction() {
        $this->_helper->layout->disableLayout();
        $this->_helper->viewRenderer->setNoRender(true);
        $profile_manager = new Application_Model_Deviceprofilemanager();
        $request = $this->getRequest();
        $params = $request->getParams();
        $resp = $profile_manager->ChangeDeviceProfile($params);
        $this->_redirect('Deviceprofilemanager/manage/devicetype_id/' . $params['devicetype'] . '/devicevendor_id/' . $params['vendor'] . '/deviceosv_id/' . $params['deviceosversion']);
    }

    public function deviceprofileformtestAction() {
        
    }

    public function deviceprofileformAction() {
        $this->_helper->layout->disableLayout();
        $device_manager = new Application_Model_Devicecimanager();

        $request = $this->getRequest();
        $params = $request->getParams();

        $devicetype_id = $request->getParam('devicetype_id');
        $devicevendor_id = $request->getParam('devicevendor_id');
        $deviceosversion_id = $request->getParam('deviceosversion_id');

        $this->view->edit = 0;
        if (isset($params['edit']) && $params['edit'] == 1) {
            if (isset($params['dp_id'])) {
                $device_profile_details = $device_manager->getDeviceProfileDetails($params['dp_id']);
                $this->view->device_profile_details = $device_profile_details;
                $this->view->edit = 1;
                $devicetype_id = $device_profile_details['devicetype_id'];
                $devicevendor_id = $device_profile_details['devicevendor_id'];
                $deviceosversion_id = $device_profile_details['deviceosversion_id'];
            }
        }

        $NascentNamespace = new Zend_Session_Namespace('SI2NascentNamespace');

        if (isset($NascentNamespace->DPParams)) {
            $this->view->device_profile_details = $NascentNamespace->DPParams;
            unset($NascentNamespace->DPParams);
        }

        $this->view->devicetype_id = $devicetype_id;
        $this->view->devicevendor_id = $devicevendor_id;
        $this->view->deviceosversion_id = $deviceosversion_id;

        $device_types = $device_manager->getdeviceTypes();
        $this->view->device_types = $device_types;

        $vendors = $device_manager->getvendors();
        $this->view->vendors = $vendors;

        $device_type = $device_manager->getdeviceTypes($devicetype_id);
        $this->view->device_type = $device_type[0];

        $vendor_details = $device_manager->getvendors($devicevendor_id);
        $this->view->vendor_details = $vendor_details[0];

        if ($devicevendor_id != 0 || $devicevendor_id != '') {
            $device_os = $device_manager->getdeviceOS($devicevendor_id);
            $this->view->device_os = $device_os;
        }
        if ($deviceosversion_id != 0 || $deviceosversion_id != '') {
            $os_version_details = $device_manager->deviceOSVDetails($deviceosversion_id);
            $this->view->os_version_details = $os_version_details[0];
        }
        $this->view->referer = $request->getHeader('referer');
    }

    public function deviceciinstancesaveAction() {

        $this->view->layout()->disableLayout();
        $this->_helper->viewRenderer->setNoRender(true);

        $identity = Zend_Auth::getInstance()->getIdentity();
        $user_name = $identity->user_name;


        $request = $this->getRequest();
        $deviceci_manager = new Application_Model_Devicecimanager();

        $res = array();
        $res['device_id'] = $request->getParam('device_id');
        $res['device_profile_id'] = $request->getParam('device_profile_id');
        $res['asp_instance_id'] = $request->getParam('asp_instance_id');
        $res['test_device'] = $request->getParam('test_device');
        $ip1 = $request->getParam('ip1');

        $details = $deviceci_manager->deviceciinstancesave($res);

        // START VERTICA LOGGING //
        $data = array();
        $data['activity'] = 'SAVE CI';
        $data['systemid'] = 200;
        $data['taskid'] = time() . "." . mt_rand(); // generate unique taskid
        $data['taskdesc'] = 'Save CI profile';
        $data['extsystemid'] = '';
        $data['actionid'] = '';
        $data['hpnataskid'] = '';
        $data['hpnasessionid'] = '';
        $data['incomingcall'] = '';
        $data['entrydatetime'] = date('Y-m-d H:i:s', time());
        $data['starttimestamp'] = date('Y-m-d H:i:s', time());
        $data['endtimestamp'] = date('Y-m-d H:i:s', time());
        $data['initiationtype'] = '';
        $data['initiatedby'] = $user_name;
        $data['status'] = 'SUCCESS';
        $data['result'] = 'SUCCESS';
        $data['resultdetail'] = $details;
        $data['targetci'] = $ip1;
        $this->vertica_log_new($data);
        $this->_redirect("Deviceprofilemanagerci/deviceciinstanceedit/device_id/{$res['device_id']}");
    }

    public function manageAction() {
        $request = $this->getRequest();

        $device_manager = new Application_Model_Devicecimanager();


        $datasource_id = $request->getParam('datasource_id');
        $accounttype_id = $request->getParam('accounttype_id');

        $this->view->accounttype_id = $accounttype_id;
        $this->view->datasource_id = $datasource_id;

        $this->_helper->layout->disableLayout();
        $SSINamespace = new Zend_Session_Namespace('SSINamespace');

        $grid = $this->grid();

        $content = $device_manager->getDeviceInstanceList($datasource_id);

        $this->view->header = $content[0]; // Updated by Sathish to show Breadcrumb

        $headers = array("Manage", "Id", "Devicename", "Ipaddress", "Ipv6address", "Org", "Deviceprofile", "Type", "Model", "Os", "Description", "Hpna", "Hpna ipaddr", "Datasource");

        $source1 = new Bvb_Grid_Source_Array($content, $headers);

        $grid->setSource($source1);

        $manage = new Bvb_Grid_Extra_Column();
        $manage->position('left')->name('Manage');
        $manage->callback(array(
            'function' => array($this, 'manage_function'),
            'params' => array('{{id}}')
                )
        );

        $grid->addExtraColumns($manage);

        $this->view->pages = $grid->deploy();
    }

    function manage_function($id) {

        $edit = "<a href='" . SITE_URL . "Deviceprofilemanagerci/deviceciinstanceview/device_id/$id'><img style='vertical-align:middle' border=0 src='" . SITE_URL . "public/images/NetSRM%20Icons/Launch%20Page%20Icons/Critical%20Icons/ticket.png' alt='View' /></a>&nbsp;&nbsp;
		<a href='" . SITE_URL . "Deviceprofilemanagerci/deviceciinstanceedit/device_id/$id'><img style='vertical-align:middle' border=0 src='" . SITE_URL . "public/images/edit.png' alt='Edit' /></a>&nbsp;&nbsp;";
        return "<div style='width:100%;display:table;text-align: left'>" .
                $edit .
                "</div>";
    }

    public function grid($id = '') {
        $view = new Zend_View();
        $view->setEncoding('ISO-8859-1');
        $config = new Zend_Config_Ini('./application/configs/grid.ini', 'production');
        $grid = Bvb_Grid::factory('Table', $config, $id);
        $grid->setEscapeOutput(false);
        $grid->setExport(array());
        $grid->setView($view);
        $grid->setShowOrderImages(false);

        return $grid;
    }

    public function getdeviceosAction() {
        $request = $this->getRequest();
        $vendor = $request->getParam('vendor');

        $device_manager = new Application_Model_Deviceprofilemanager();
        $device_os_list = $device_manager->getdeviceOS($vendor);


        $data = array();
        for ($i = 0; $i < count($device_os_list); $i++) {
            $data[$i]['key'] = $device_os_list[$i]['id'];
            $data[$i]['value'] = $device_os_list[$i]['name'];
        }

        echo Zend_Json::encode($data);

        $this->_helper->layout->disableLayout();
        $this->_helper->viewRenderer->setNoRender(true);
    }

    public function getdeviceprofiledetailsAction() {
        $this->_helper->layout->disableLayout();
        $this->_helper->viewRenderer->setNoRender(true);
        $request = $this->getRequest();
        $device_profile_id = $request->getParam('device_profile_id');
        $device_manager = new Application_Model_Devicecimanager();
        $device_os_list = $device_manager->getDeviceProfileDetails($device_profile_id);
        echo Zend_Json::encode($device_os_list);
    }

    public function getvendormodelAction() {
        $request = $this->getRequest();
        $vendor = $request->getParam('vendor');

        $select = "SELECT id, model FROM `devicevendormodel` WHERE vendorid = $vendor ORDER BY model";
        $stmt = $this->_db->query($select);
        $result = $stmt->fetchAll();

        $data = array();
        for ($i = 0; $i < count($result); $i++) {
            $data[$i]['key'] = $result[$i]['id'];
            $data[$i]['value'] = $result[$i]['model'];
        }

        echo Zend_Json::encode($data);

        $this->_helper->layout->disableLayout();
        $this->_helper->viewRenderer->setNoRender(true);
    }

    public function getmodelversionAction() {
        $request = $this->getRequest();
        $vendormodel = $request->getParam('vendormodel');

        $select = "SELECT id, version FROM `devicemodelversion` WHERE devicevendormodelid = $vendormodel ORDER BY version";
        $stmt = $this->_db->query($select);
        $result = $stmt->fetchAll();

        $data = array();
        for ($i = 0; $i < count($result); $i++) {
            $data[$i]['key'] = $result[$i]['id'];
            $data[$i]['value'] = $result[$i]['version'];
        }

        echo Zend_Json::encode($data);

        $this->_helper->layout->disableLayout();
        $this->_helper->viewRenderer->setNoRender(true);
    }

    public function getosversionAction() {
        $request = $this->getRequest();
        $deviceos_id = $request->getParam('deviceos');

        $select = "SELECT `id`, `version` FROM `deviceosversions` WHERE deviceosid = $deviceos_id GROUP BY `version`";
        $stmt = $this->_db->query($select);
        $result = $stmt->fetchAll();

        $data = array();
        for ($i = 0; $i < count($result); $i++) {
            $data[$i]['id'] = $result[$i]['id'];
            $data[$i]['version'] = $result[$i]['version'];
        }

        echo Zend_Json::encode($data);

        $this->_helper->layout->disableLayout();
        $this->_helper->viewRenderer->setNoRender(true);
    }

    public function getosversionreleaseAction() {
        $request = $this->getRequest();
        $deviceos_version = $request->getParam('deviceosversion');

        $select = "SELECT `id`, `revision` FROM `deviceosversionrevisions` WHERE deviceosversionid = $deviceos_version";
        $stmt = $this->_db->query($select);
        $result = $stmt->fetchAll();

        $data = array();
        for ($i = 0; $i < count($result); $i++) {
            $data[$i]['id'] = $result[$i]['id'];
            $data[$i]['revision'] = $result[$i]['revision'];
        }

        echo Zend_Json::encode($data);

        $this->_helper->layout->disableLayout();
        $this->_helper->viewRenderer->setNoRender(true);
    }

}
