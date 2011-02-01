<?php

session_start();

// FIX, these should be encrypted.  Note: will white screen if incorrect.
$FCM_config['sfapi_username'] =$options['salesforce_username'];
$FCM_config['sfapi_pass'] =$options['salesforce_pass'];
$FCM_config['sfapi_security_token'] =$options['salesforce_token']; // new for September '08
$FCM_config['sfapi_pass_and_token'] = $FCM_config['sfapi_pass'] . $FCM_config['sfapi_security_token'];
$FCM_config['sfapi_webroot_path'] = $_SERVER['DOCUMENT_ROOT'];
$FCM_config['sfapi_offroot_path'] = dirname(__FILE__);
$FCM_config['sfapi_conn_kit_path'] = $FCM_config['sfapi_offroot_path'] . '/php5-1.1.1';
$FCM_config['sfapi_wsdl'] = 'partner.wsdl.xml';
// no config changes needed beneath this line

// start loading default sf ws api stuff


//require_once($FCM_config['sfapi_conn_kit_path'] . '/soapclient/SforceEnterpriseClient.php');
//$mySforceConnection = new SforceEnterpriseClient();

require_once($FCM_config['sfapi_conn_kit_path'] . '/soapclient/SforcePartnerClient.php');
$mySforceConnection = new SforcePartnerClient();

$mySoapClient = $mySforceConnection->createConnection($FCM_config['sfapi_offroot_path'] . '/' . $FCM_config['sfapi_wsdl']);
$mySforceLogin = $mySforceConnection->login($FCM_config['sfapi_username'], $FCM_config['sfapi_pass_and_token']);
require_once ($FCM_config['sfapi_conn_kit_path'] . '/soapclient/SforceHeaderOptions.php');

ini_set("soap.wsdl_cache_enabled", "0");

?>
