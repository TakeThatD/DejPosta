<?php
//initialize the framework:
// - load config, messages - prepare functions returning this global objects
// - prepare functions loading smarty and database on demand (only once)
// - load core functions and validators
// - load user roles from session and load $action from request

require_once dirname(__FILE__).'/core/Config.class.php';
$conf = new Config();
require_once dirname(__FILE__).'/config.php'; //set configuration

function getConf(){ global $conf; return $conf; }

//Load Messages class and create object
require_once dirname(__FILE__).'/core/Messages.class.php';
$msgs = new Messages();

function getMessages(){	global $msgs; return $msgs; }

$smarty = null;	//Prepare for Smarty, create once - only when needed
function getSmarty(){
	global $smarty;
	if (!isset($smarty)){ //Create smarty and assign configuration and messages
		require_once dirname(__FILE__).'/lib/smarty/Smarty.class.php';
		$smarty = new Smarty();
		$smarty->assign('conf',getConf());
		$smarty->assign('msgs',getMessages());
	}
	return $smarty;
}

$db = null; //Prepare for Database, load Medoo library and create connection once - only when needed
function getDB(){
	global $conf,$db;
	if (!isset($db)){
		require_once dirname(__FILE__).'/lib/medoo/medoo.php';
		$db = new \Medoo\Medoo([
			'database_type' => &$conf->db_type,
			'server' => &$conf->db_server,
			'database_name' => &$conf->db_name,
			'username' => &$conf->db_user,
			'password' => &$conf->db_pass,
			'charset' => &$conf->db_charset,
			'port' => &$conf->db_port,
			'prefix' => &$conf->db_prefix,
			'option' => &$conf->db_option
		]);
	}
	return $db;
}

require_once dirname(__FILE__).'/core/core_functions.php'; //load core functions
require_once dirname(__FILE__).'/core/validators.php'; //load validators
session_start(); //start or continue session
$conf->roles = null != getFromSession('_roles') ? unserialize(getFromSession('_roles')) : array(); //load roles
$action = getFromRequest(getConf()->action_param); //get action to perform from request
