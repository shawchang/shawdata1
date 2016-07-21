<?php
error_reporting (E_ALL ^ E_NOTICE ^ E_STRICT);

date_default_timezone_set('GMT0');
defined('APPLICATION_PATH')
    || define('APPLICATION_PATH', realpath(dirname(__FILE__) . '/application'));

// Define application environment. $_SERVER works when the PHP is executed via an HTTPS request, and getenv() works when executing PHP script via cron job. So both situations are covered here.
if ($_SERVER['HTTP_HOST'] === 'si2.ssn.hp.com' || getenv("HOSTNAME") === 'castssi2001' || getenv("HOSTNAME") === 'cammssi2001') {
	defined('APPLICATION_ENV') || define('APPLICATION_ENV', (getenv('APPLICATION_ENV') ? getenv('APPLICATION_ENV') : 'staging'));
} else {
	defined('APPLICATION_ENV') || define('APPLICATION_ENV', (getenv('APPLICATION_ENV') ? getenv('APPLICATION_ENV') : 'development'));
}    

// Ensure library/ is on include_path
set_include_path(implode(PATH_SEPARATOR, array(
    realpath(APPLICATION_PATH . '/../library'),
    realpath(APPLICATION_PATH . '/../pear'),
    get_include_path(),
)));

include "Zend/Loader/Autoloader.php";
$autoloader = Zend_Loader_Autoloader::getInstance();
$autoloader->registerNamespace('Bvb_');
$autoloader->registerNamespace('My_');

/** Zend_Application */
require_once 'Zend/Application.php';

// Create application, bootstrap, and run
$application = new Zend_Application(
    APPLICATION_ENV,
    APPLICATION_PATH . '/configs/application.ini'
);

//require_once 'myexec.php';
define('SITE_URL', 'http://'.$_SERVER['HTTP_HOST'].'/si2-nascent/');
define('IMAGE_PATH', 'http://'.$_SERVER['HTTP_HOST'].'/si2-nascent/public/images/');

define('PROD_FQDN', 'http://si2.ssn.hp.com/SI2/');
define('PROJECT_TITLE', 'SI2-NASCENT');
define('PROJECT_PATH', '/si2-nascent');
define('PROJECT_REL_PATH', APPLICATION_PATH);
define('GUI_REDIRECT_URL', 'http://'.$_SERVER['HTTP_HOST'].'/si2-nascent/ipam/actiongrid/');
define('GUI_TICKET_PAGE_URL', 'http://'.$_SERVER['HTTP_HOST'].'/si2-nascent/ipam/requestgrid/');


//define('_TMP_FILES_PATH_', APPLICATION_PATH."/../tmpfiles/");
define('_TMP_FILES_PATH_','/usr/local/apache2/htdocs/si2-nascent/tmpfiles/');

define('WEBSERVICE_HOST', 'http://localhost');
//define('WEBSERVICE_USERNAME', 'chittaranjan.behera@hp.com');
//define('WEBSERVICE_PASSWORD', 'testlab30');

define('_ACCOUNTNAME_PARAMNAME_', 'AccountName');
define('_TARGETDEVICE_PARAMNAME_', 'TargetCI');


define('CLIENT_TYPE', "HPES");

include_once('codes.php');
include_once('constants.php');

$application->bootstrap();

/** Cronjobs don't need all the extras **/
if (!defined('_CRONJOB_') || __CRONJOB_ == false) {
	$application->run();
}
