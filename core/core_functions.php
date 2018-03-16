<?php
function getFromRequest($param_name,$required=false,$required_message=null,$index=null){
	if (isset($_REQUEST [$param_name])){
		return $_REQUEST [$param_name];
	} else {
		if ($required) getMessages()->addMessage(new Message($required_message,Message::ERROR),$index);
		return null;
	}
}
function getFromGet($param_name,$required=false,$required_message=null,$index=null){
	if (isset($_GET [$param_name])){
		return $_GET [$param_name];
	} else {
		if ($required) getMessages()->addMessage(new Message($required_message,Message::ERROR),$index);
		return null;
	}
}
function getFromPost($param_name,$required=false,$required_message=null,$index=null){
	if (isset($_POST [$param_name])){
		return $_POST [$param_name];
	} else {
		if ($required) getMessages()->addMessage(new Message($required_message,Message::ERROR),$index);
		return null;
	}
}
function getFromCookies($param_name,$required=false,$required_message=null,$index=null){
	if (isset($_COOKIES [$param_name])){
		return $_COOKIES [$param_name];
	} else {
		if ($required) getMessages()->addMessage(new Message($required_message,Message::ERROR),$index);
		return null;
	}
}
function getFromSession($param_name,$required=false,$required_message=null,$index=null){
	if (isset($_SESSION [$param_name])){
		return $_SESSION [$param_name];
	} else {
		if ($required) getMessages()->addMessage(new Message($required_message,Message::ERROR),$index);
		return null;
	}
}
function forwardTo($page){
	global $action;
	$action = $page;
	include getConf()->root_path.getConf()->action_script;
	exit;
}
function redirectTo($page){
	header("Location: ".getConf()->action_url.$page);
	exit;
}
function control($path,$controller,$method,$roles = null){
	if ($roles != NULL){
		$found=false;
		if (is_array($roles)){
			foreach($roles as $role){
				if (in_array($role,getConf()->roles)){ $found=true; break; }
			}
		} else {
			if (in_array($roles,getConf()->roles)) $found=true;
		}
		if (!$found) forwardTo(getConf()->login_action);
	}
	include_once getConf()->root_path.$path.$controller.'.class.php';
	$ctrl = new $controller;
	if(is_callable(array($ctrl, $method))){
		$ctrl->$method();
	}
	exit;
}
function addRole($role){
	getConf()->roles [] = $role;
	$_SESSION['_roles'] = serialize(getConf()->roles);
}
function inRole($role){
	if ($role != NULL && in_array($role,getConf()->roles)) return true;
	return false;
}
function requireRole($role,$failaction = null){
	if ($role != NULL && !in_array($role,getConf()->roles)){
		if (isset($failaction))	forwardTo($failaction);
		else forwardTo(getConf()->login_action);
	}
}
function storeMessages(){
	$_SESSION['_messages'] = serialize(getMessages());
}
function loadMessages($keep = false){
	global $msgs;
	if (isset($_SESSION['_messages'])){
		$msgs = unserialize($_SESSION['_messages']);
		if (!$keep) unset($_SESSION['_messages']);
	}
}
function storeObject($name,&$object){
	if (isset($object)){
		$_SESSION[$name] = serialize($object);
	}
}
function loadObject($name,$keep = false){
	if (isset($_SESSION[$name])){
		$r = unserialize($_SESSION[$name]);
		if (!$keep) unset($_SESSION[$name]);
		return $r;
	}
	return null;
}
function storeData($name,&$data){
	if (isset($data)){
		$_SESSION[$name] = json_encode($data);
	}
}
function loadData($name,$keep = false){
	if (isset($_SESSION[$name])){
		$r = json_decode($_SESSION[$name]);
		if (!$keep) unset($_SESSION[$name]);
		return $r;
	}
	return null;
}
?>
