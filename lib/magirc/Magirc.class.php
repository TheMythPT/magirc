<?php
// $Id$

class Magirc {
	var $db = null;
	var $cfg = null;
	var $tpl = null;
	var $denora = null;

	function Magirc() {
		$this->db = new Magirc_DB;
		
		$query = "SHOW TABLES LIKE 'magirc_config'";
		$this->db->query($query, SQL_INIT);
		$this->tpl = new Magirc_Smarty;
		if (!$this->db->record) {
			$this->displayError('Database table missing. Please run setup.');
		}
		$this->cfg = new Config();
		$this->denora = new Denora;
	}
	
	// Returns session status
	function sessionStatus() {
		if (!isset($_SESSION["loginUsername"])) {
			$_SESSION["message"] = "Access denied";
			return false;
		}
		if (!isset($_SESSION["loginIP"]) || ($_SESSION["loginIP"] != $_SERVER["REMOTE_ADDR"])) {
			$_SESSION["message"] = "Access denied";
			return false;
		}
		return true;
	}
	
	// Gets and returns the given url parameter depending on what it is
	function getUrlParameter($param) {
		switch ($param) {
			case 'section':
				$param = isset($_GET['section']) ? $_GET['section'] : 'home';
				break;
			case 'action':
				$param = isset($_GET['action']) ? $_GET['action'] : 'main';
				break;
			default:
				$param = isset($_GET[$param]) ? $_GET[$param] : '';
		}
		return stripslashes(htmlspecialchars(basename($param)));
	}
	
	// Displays an error page with the given message
	function displayError($err_msg) {
		$this->tpl->assign('err_msg', $err_msg);
		$this->tpl->assign('server', $_SERVER);
		$this->tpl->display('error.tpl');
	}
	
	//TODO: implement :)
	function getPage($page) {
		return NULL;
	}
}

?>