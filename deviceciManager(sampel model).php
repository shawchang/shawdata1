
<?php

/**
 * File :  application/model/Devicecimanager.php
 * Description : Sample Model
 * Created Date :  2015-11-18
 * Date of Change :  2016-8-30
 * Created by :  Shaw Chang
 * E-mail : shaw.chang@hpe.com
 * Changed by :  Shaw Chang
 * Change log : adding new code comment section.
 * Version no. : 1.0.
 */
if (!function_exists('ddd')) {

    function ddd($input) {
        var_dump($input);
        die();
    }

}

class Application_Model_Devicecimanager extends Zend_Db_Table_Abstract {

    private $db;

    public function __construct() {
        $this->db = $this->getDefaultAdapter();
        $vertica = new Application_Model_Verticamodel();
        $this->vertica = $vertica;
    }

    public function getaccountTypes($userabc_id, $id = '') {
        if ($id == '') {
            $stmt = $this->db->query("SELECT id, logicalentity AS `client`, l.org_id "
                    . "FROM LOGICALENTITY l, USER_CLIENTS uc "
                    . "WHERE l.entitytype = 23 AND uc.user_id = ? AND uc.client_id = l.id "
                    . "ORDER BY logicalentity", array($userabc_id));
            $result = $stmt->fetchAll();
        } else {
            $stmt = $this->db->query("SELECT * FROM `LOGICALENTITY` WHERE id=" . $id);

            $stmt = $this->db->query("SELECT id, logicalentity AS `client`, l.org_id  FROM LOGICALENTITY l, USER_CLIENTS uc WHERE l.entitytype = 23 AND uc.user_id = ? AND l.org_id = ? AND uc.client_id = l.id ORDER BY logicalentity", array($userabc_id, $id));

            $result = $stmt->fetch();
        }

        return $result;
    }

    public function getNotes($device_id) {
        $stmt = $this->db->query("select * from ci_notes where deviceid=" . $device_id . " ");
        $result = $stmt->fetchAll();
        return $result;
    }

    public function getASPinstance() {

        $select = "select asp_instances.id, asp_instances.name from asp_instances
                            left join asp_systemtypes on asp_systemtypes.id = asp_instances.aspsystemtypeid
                            left join asp_servicetypes on asp_servicetypes.id = asp_systemtypes.asp_servicetypeid
                            where asp_servicetypes.name = 'HPNA'";

        $stmt = $this->db->query($select);
        $result = $stmt->fetchAll();
        return $result;
    }

    public function getdeviceciinstance($accounttype_id) {
        $stmt = $this->db->query("select distinct *
                    from datasources where datasources.orgid = $accounttype_id");
        $result = $stmt->fetchAll();
        return $result;
    }

    function getActions($targetip) {
        $select = $this->db->select()
                ->from(array('da' => 'deviceactions'))
                ->join(array('devices' => 'devices'), 'da.deviceprofileid=devices.deviceprofileid', array('ipaddress' => 'ipaddress'))
                ->where('devices.ipaddress = ?', $targetip);
        $stmt = $this->db->query($select);
        $result = $stmt->fetchAll();
        return $result;
    }

    function getCommonActionParameters($commonactionid) {
        $select = $this->db->select()
                ->from(array('cap' => 'commonactionparameters'))
                ->where('cap.commonactionid = ?', $commonactionid);
        $stmt = $this->db->query($select);
        $result = $stmt->fetchAll();
        return $result;
    }

    function getDeviceActionParameters($deviceactionid) {
        $select = $this->db->select()
                ->from(array('dap' => 'deviceactionparameters'))
                ->where('dap.deviceactionid = ?', $deviceactionid);
        $stmt = $this->db->query($select);
        $result = $stmt->fetchAll();
        return $result;
    }

    function getDeviceConfigurations($id) {
        $select = "SELECT id, timestamp, description, text FROM `deviceconfig` WHERE deviceid = $id  ORDER BY timestamp DESC";
        $stmt = $this->db->query($select);
        $result = $stmt->fetchAll();
        return $result;
    }

    function Getdeviceactionid($deviceprofile_id, $action_id) {
        $select = " SELECT id, commonactionid, deviceprofileid from deviceactions where commonactionid=$action_id and deviceprofileid=$deviceprofile_id ";

        $stmt = $this->db->query($select);
        $result = $stmt->fetch();
        return $result;
    }

    function getCommonActions($targetip, $org_id) {
        $select = "SELECT 'CommonAction' AS
                TYPE, NULL as version, deviceactions.deviceprofileid ,commonactions.id, commonactions.name, commonactions.desc AS description
                FROM commonactions
                JOIN deviceactions ON deviceactions.commonactionid = commonactions.id
                JOIN devices ON deviceactions.deviceprofileid = devices.deviceprofileid
                WHERE devices.ipaddress = '" . $targetip . "'
                AND devices.orgid = $org_id
                UNION
                SELECT 'DeviceAction' AS
                TYPE, deviceactions.version ,deviceactions.deviceprofileid ,deviceactions.id, deviceactions.deviceactionname AS name, deviceactions.description
                FROM deviceactions
                JOIN devices ON deviceactions.deviceprofileid = devices.deviceprofileid
                WHERE devices.ipaddress = '" . $targetip . "'
                AND devices.orgid = $org_id
                AND deviceactions.deviceprofileid !='0' order by version, name ";

        $stmt = $this->db->query($select);
        $result = $stmt->fetchAll();
        return $result;
    }

    function getActions1($targetip, $org_id) {
        $select = "SELECT `da`.`id` AS `deviceaction_id`, `da`.`deviceactionname` AS `deviceaction_name`, `da`.`description` AS `deviceaction_description` FROM `deviceactions` AS `da` INNER JOIN `devices` ON da.deviceprofileid=devices.deviceprofileid WHERE devices.ipaddress = '" . $targetip . "' AND devices.orgid = '" . $org_id . "' ";

        $stmt = $this->db->query($select);
        $result = $stmt->fetchAll();
        return $result;
    }

    public function getdeviceVendors($id) {
        $stmt = $this->db->query("SELECT `vendors`.`id`, `vendors`.`name` FROM  `vendors` 
				JOIN `devicevendormodel` AS DVM ON DVM.vendorid=`vendors`.id
				JOIN `devicemodelversion` as DMV ON DMV.devicevendormodelid=DVM.id
				JOIN `deviceprofiles` AS DP ON  DMV.id=DP.devicemodelversionid
				WHERE DP.devicetypeid=" . $id . " GROUP BY `vendors`.name ");
        $result = $stmt->fetchAll();
        return $result;
    }

    public function getdeviceOSVersions($devicevendor_id, $devicetype_id) {
        $stmt = $this->db->query("SELECT DOSV.`id` as deviceosversion_id, DOS.`name` as deviceos_name, DOSV.`version` as deviceosversion_name 
                FROM `deviceosversions` AS DOSV
                JOIN `deviceos` AS DOS ON DOS.id=DOSV.deviceosid
                JOIN `deviceprofiles` AS DP ON DOSV.id=DP.deviceosversionid
                WHERE DOS.vendorid=" . $devicevendor_id . " 
                AND DP.devicetypeid=" . $devicetype_id . " 
                GROUP BY DOSV.`version` ");

        $result = $stmt->fetchAll();
        return $result;
    }

    public function deviceOSVDetails($device_osv_id = '') {
        $stmt = $this->db->query("SELECT DOSV.id as osv_id, DOS.id as os_id, DOS.name as os_name, DOSV.`version` FROM `deviceosversions` AS DOSV
				JOIN `deviceos` AS DOS ON DOS.id=DOSV.deviceosid
				WHERE DOSV.id=" . $device_osv_id . "  ");
        $result = $stmt->fetchAll();
        return $result;
    }

    public function getvendors($id = "") {
        if ($id == '') {
            $stmt = $this->db->query("SELECT * FROM `vendors`");
        } else {
            $stmt = $this->db->query("SELECT * FROM `vendors` WHERE id=" . $id);
        }
        $result = $stmt->fetchAll();
        print_r($result);
        return $result;
    }

    public function getvendorModels() {
        $stmt = $this->db->query("SELECT * FROM `devicevendormodel`");
        $result = $stmt->fetchAll();
        return $result;
    }

    public function getdeviceVersions() {
        $stmt = $this->db->query("SELECT * FROM  `devicemodelversion`");
        $result = $stmt->fetchAll();
        return $result;
    }

    public function getdeviceOS($vendor_id = "") {
        if ($vendor_id == "") {
            $stmt = $this->db->query("SELECT * FROM  `deviceos`");
        } else {
            $stmt = $this->db->query("SELECT * FROM  `deviceos` WHERE vendorid=" . $vendor_id);
        }
        $result = $stmt->fetchAll();
        return $result;
    }

    function getDeviceCIDetails($device_id) {
        $query = "select  devices.devicename
                ,devices.test_device
		,devices.ipaddress
		,devices.ipv6address
		,LOGICALENTITY.logicalentity as 'org'
		,LOGICALENTITY.org_id as 'accounttype_id'
		,deviceprofiles.name as 'deviceprofile'
		,deviceprofiles.id as 'dp_id'
		,devicetypes.name as 'type'
                ,devicetypes.id as 'devicetype_id'
		,vendors.name as 'vendor'
                ,vendors.id as 'vendor_id'
		,devicevendormodel.model
		,devicemodelversion.version as 'modelversion'
		,devicemodelversionrevisions.revision as 'modelversionrevision'
		,deviceos.name 'os'
                ,deviceos.id as 'os_id'
		,deviceosversions.version as 'osversion'
                ,deviceosversions.id as 'deviceosversion_id' /* codebase */
		,deviceosversionrevisions.revision as 'osversionrevision'
		,devices.description
		,asp_instances.name as 'hpna'
                ,asp_instances.id as 'hpna_id'
		,asp_instances.ipaddress as 'hpna_ipaddr'
		,datasources.name as 'datasource'
		,datasources.id as 'datasource_id'	
		from devices
		left join  LOGICALENTITY on  devices.orgid =  LOGICALENTITY.org_id
		left join  deviceprofiles on  deviceprofiles.id =  devices.deviceprofileid
		left join  devicetypes on  devicetypes.id =  deviceprofiles.devicetypeid
		left join  devicevendormodel on  devicevendormodel.id =  deviceprofiles.devicevendormodelid
		left join  devicemodelversion on  devicemodelversion.id =  deviceprofiles.devicemodelversionid
		left join  devicemodelversionrevisions on  devicemodelversionrevisions.id =  deviceprofiles.devicemodelversionid
		left join  vendors on  vendors.id =  devicevendormodel.vendorid
		left join  deviceos on  deviceos.id =  deviceprofiles.deviceosid
		left join  deviceosversions on  deviceosversions.deviceosid =  deviceprofiles.deviceosversionid
		left join  deviceosversionrevisions on  deviceosversionrevisions.id =  deviceprofiles.deviceosversionrrevisionid
		left join  asp_instances on  devices.aspinstanceid =  asp_instances.id
		left join  datasources on  devices.datasourceid =  datasources.id
		where  devices.id = $device_id";

        $stmt = $this->db->query($query);
        $content = $stmt->fetchAll();
        return $content;
    }

    public function getDeviceInstanceList($datasource_id) {
        $query = "select  devices.id,  devices.devicename
				,devices.ipaddress
				,devices.ipv6address
				,LOGICALENTITY.logicalentity as 'org'
				,deviceprofiles.name as 'deviceprofile'
				,devicetypes.name as 'type'
				,devicevendormodel.model
				,concat(deviceos.name,' ',deviceosversions.version) as 'os'
				,devices.description
				,asp_instances.name as 'hpna'
				,asp_instances.ipaddress as 'hpna_ipaddr'
				,datasources.name as 'datasource' 
				from  devices
				left join  LOGICALENTITY on  devices.orgid =  LOGICALENTITY.org_id
				left join  deviceprofiles on  deviceprofiles.id =  devices.deviceprofileid
				left join  devicetypes on  devicetypes.id =  deviceprofiles.devicetypeid
				left join  devicevendormodel on  devicevendormodel.id =  deviceprofiles.devicevendormodelid
				left join  deviceos on  deviceos.id =  deviceprofiles.deviceosid
				left join  deviceosversions on  deviceprofiles.deviceosversionid =  deviceosversions.id
				left join  asp_instances on  devices.aspinstanceid =  asp_instances.id
				left join  datasources on  devices.datasourceid =  datasources.id
				where  devices.datasourceid = " . $datasource_id;
        $stmt = $this->db->query($query);
        $content = $stmt->fetchAll();
        return $content;
    }

    public function getDeviceInstanceListgroupby($datasource_id, $groupby) {
        $query = "select  devices.id,  devices.devicename
				,devices.ipaddress
				,devices.ipv6address
				,LOGICALENTITY.logicalentity as 'org'
				,deviceprofiles.name as 'deviceprofile'
				,devicetypes.name as 'type'
				,devicevendormodel.model
				,concat(deviceos.name,' ',deviceosversions.version) as 'os'
				,devices.description
				,asp_instances.name as 'hpna'
				,asp_instances.ipaddress as 'hpna_ipaddr'
				,datasources.name as 'datasource'
				from  devices
				left join  LOGICALENTITY on  devices.orgid =  LOGICALENTITY.org_id
				left join  deviceprofiles on  deviceprofiles.id =  devices.deviceprofileid
				left join  devicetypes on  devicetypes.id =  deviceprofiles.devicetypeid
				left join  devicevendormodel on  devicevendormodel.id =  deviceprofiles.devicevendormodelid
				left join  deviceos on  deviceos.id =  deviceprofiles.deviceosid
				left join  deviceosversions on  deviceprofiles.deviceosversionid =  deviceosversions.id
				left join  asp_instances on  devices.aspinstanceid =  asp_instances.id
				left join  datasources on  devices.datasourceid =  datasources.id
				where  devices.datasourceid = " . $datasource_id;
        if (!empty($groupby)) {
            $query .= ' GROUP BY ' . $groupby;
        }
        $stmt = $this->db->query($query);
        $content = $stmt->fetchAll();
        return $content;
    }

    public function ChangeDeviceProfile($params) {
        $select = '';
        if (!empty($params['profile_name']))
            $select .= "`name` ='" . $params['profile_name'] . "'";
        if (!empty($params['devicetype']))
            $select .= ",`devicetypeid` =" . $params['devicetype'] . "";
        if (!empty($params['deviceversion']))
            $select .= ",`devicemodelversionid` =" . $params['deviceversion'] . "";
        if (!empty($params['deviceosversion']))
            $select .= ",`deviceosversionid` =" . $params['deviceosversion'] . "";
        if (!empty($params['profile_description']))
            $select .= ",`description` ='" . $params['profile_description'] . "'";

        $select = ltrim($select, ',');
        $select = rtrim($select, ',');
        $select = "UPDATE `deviceprofiles` SET " . $select;
        $select .= " WHERE id IN (" . $params['profile_id'] . ")";
        $this->db->query($select);
        return true;
    }

    public function RealConfiguration($Status1, $Timestamp, $Status2, $fqdn, $Text, $deviceid) {

        if ($Status1 == 200) {
            if ($Status2 == 200) {
                $temp = array('deviceid' => $deviceid,
                    'timestamp' => $Timestamp,
                    'description' => $fqdn,
                    'text' => $Text,);
                $this->db->insert('deviceconfig', $temp);
            } else {
                echo "<script>alert('Failed on 200');</script>";
            }
        } else {
            echo "<script>alert('Failed on 200');</script>";
        }
    }

    public function getDeviceProfileDetails($dp_id) {

        $id = trim($id);
        $stmt = $this->db->query("select deviceprofiles.id
                            , deviceprofiles.name
                            , devicetypes.name as devicetype
                            , vendors.name
                            , devicevendormodel.model
                            , devicemodelversion.version as modelversion
                            , devicemodelversionrevisions.revision as modelrevision
                            , deviceos.name as os
                            , deviceosversions.version as osversion
                            , deviceosversionrevisions.revision as osrevision
                            from deviceprofiles
                            left join devicetypes on devicetypes.id = deviceprofiles.devicetypeid
                            left join devicevendormodel on devicevendormodel.id = deviceprofiles.devicevendormodelid
                            left join vendors on vendors.id = devicevendormodel.vendorid
                            left join devicemodelversion on devicemodelversion.id = deviceprofiles.devicemodelversionid
                            left join devicemodelversionrevisions on devicemodelversionrevisions.id = deviceprofiles.devicemodelversionid
                            left join deviceos on deviceos.id = deviceprofiles.deviceosid
                            left join deviceosversions on deviceosversions.id = deviceprofiles.deviceosversionid
                            left join deviceosversionrevisions on deviceosversionrevisions.deviceosversionid = deviceprofiles.deviceosversionrrevisionid
                            where deviceprofiles.id = ? ", array($dp_id));
        $result = $stmt->fetch();
        return $result;
    }

    public function AddDeviceProfile($params) {

        $this->db->query("INSERT INTO `deviceprofiles`  (`name`, `devicetypeid`, `devicevendormodelid`,  `devicemodelversionid`, `deviceosid`, `deviceosversionid`, `deviceosversionrrevisionid`, description) VALUES " .
                "('" . trim($params['profile_name']) . "', " . trim($params['devicetype']) . ", " . trim($params['vendormodel']) . ", " . trim($params['deviceversion']) . " , " . trim($params['deviceos']) . " , " . trim($params['deviceosversion']) . " , " . trim($params['deviceosrelease']) . ", '" . trim($params['profile_description']) . "')");

        $reference_id = $this->db->lastInsertId();
        return $reference_id;
    }

    public function getaccounttype_one($id) {
        $select = "SELECT `name` FROM `orgs` WHERE `id` = " . $id;
        $result = $this->db->query($select);
        return $result;
    }

    public function getdeviceciinstance_one($id) {
        $select = "SELECT `name` FROM `datasources` WHERE `id` = " . $id;
        $result = $this->db->query($select);
        return $result;
    }

    public function deviceciinstancesave($params) {

        $query = "SELECT * FROM `devices` WHERE id = {$params['device_id']}";
        $stmt = $this->db->query($query);
        $result = $stmt->fetch();
        if ($result['deviceprofileid'] != $params['device_profile_id']) {

            $query1 = "SELECT * FROM `deviceprofiles` WHERE id = {$result['deviceprofileid']}";
            $stmt1 = $this->db->query($query1);
            $result1 = $stmt1->fetch();
            $old_profile_name = $result1['name'];

            $query11 = "SELECT * FROM `deviceprofiles` WHERE id = {$params['device_profile_id']}";
            $stmt11 = $this->db->query($query11);
            $result11 = $stmt11->fetch();
            $new_profile_name = $result11['name'];

            $tmp1 = "Device Profile changed from " . $old_profile_name . " to " . $new_profile_name;
        }

        if ($result['aspinstanceid'] != $params['asp_instance_id']) {

            $query2 = "SELECT * FROM `asp_instances` WHERE id = {$result['aspinstanceid']}";
            $stmt2 = $this->db->query($query2);
            $result2 = $stmt2->fetch();

            $old_asp_instance = $result2['name'];

            $query3 = "SELECT * FROM `asp_instances` WHERE id = {$params['asp_instance_id']}";
            $stmt3 = $this->db->query($query3);
            $result3 = $stmt3->fetch();
            $new_asp_instance = $result3['name'];

            $tmp2 = "ASP instance changed from " . $old_asp_instance . " to " . $new_asp_instance;
        }


        if ($result['test_device'] != $params['test_device']) {

            $old_test_device = $result['test_device'];
            $new_test_device = $params['test_device'];
            $tmp3 = "test_device changed from " . $old_test_device . " to " . $new_test_device;
        }

        $tmp_all = $tmp1 . "\n" . $tmp2 . "\n" . "$tmp3";


        $select = "UPDATE `devices` SET deviceprofileid = {$params['device_profile_id']}, aspinstanceid = {$params['asp_instance_id']}, test_device = {$params['test_device']} ";

        $select .= " WHERE id = {$params['device_id']}";
        $this->db->query($select);
        return $tmp_all;
    }

    public function getautomationactivitylog($taskid) {
        $stmt = $this->db->query("SELECT * FROM automation_activity_log WHERE automation_activity_log.taskid = '" . $taskid . "' ORDER BY `automation_activity_log`.`starttimestamp` DESC");

        $result = $stmt->fetchAll();
        return $result;
    }

    public function cinotes($id = '', $userid, $Timestamp, $deviceid, $note) {

        $temp = array('id' => $id,
                  'userid' => $userid,
               'timestamp' => $Timestamp,
                'deviceid' => $deviceid,
                    'note' => $note,);
        $this->db->insert('ci_notes', $temp);

        //     print_r($temp);
        //     exit();
    }

    public function cinotesedit($device_ip, $ip) {
        $stmt = $this->db->query("select note, id from ci_notes where deviceid = '" . $device_ip . "' and id = '" . $ip . "'");
        $result = $stmt->fetch();
        return $result;
    }

    function cinotesupdate($id, $note) {

        $this->db->query("UPDATE `ci_notes` SET `note` = '$note' WHERE `ci_notes`.`id` =$id");
        return false; // test
    }

    public function getciactivityhistory($ip) {
        $stmt = $this->db->query("select automation_activity_log.id
                , automation_activity_log.activity
                , automation_activity_log.taskdesc
                , automation_activity_log.targetci
                , devices.devicename as dn
                , automation_activity_log.entrydatetime
                , automation_activity_log.status
                , automation_activity_log.initiatedby
                , automation_activity_log.result
		, automation_activity_log.resultdetail
                , automation_activity_log.taskid
                from automation_activity_log
                left join devices on devices.ipaddress = '" . $ip . "'
                where targetci = '" . $ip . "'
                and automation_activity_log.id = (SELECT MAX(a.id) FROM automation_activity_log a where a.taskid = automation_activity_log.taskid)
                order by automation_activity_log.entrydatetime DESC LIMIT 0 , 30");
        $result = $stmt->fetchAll();
        return $result;
    }

    public function getactivitylogbyid($id) {
        $stmt = $this->db->query("SELECT * FROM automation_activity_log WHERE automation_activity_log.id = " . $id);
        $result = $stmt->fetch();
        return $result;
    }

    public function isBase64($s) {

        if (empty($s))
            return false;
        // Check if there are valid base64 characters
        if (!preg_match('/^[a-zA-Z0-9\/\r\n+]*={0,2}$/', $s))
            return false;

        // Decode the string in strict mode and check the results
        $decoded = base64_decode($s, true);

        if (false === $decoded)
            return false;

        // Encode the string again
        if (base64_encode($decoded) != $s)
            return false;

        return true;
    }

}
