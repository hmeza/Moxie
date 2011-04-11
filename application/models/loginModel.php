<?php
include_once '../Zend/Zend/Db/Table.php';
include_once '../Zend/Zend/Registry.php';

class loginModel extends Zend_Db_Table_Abstract {
	protected $_name = 'users';
	protected $_primary = 'id';
	
	public function __construct() {
		$this->_db = Zend_Registry::get('db');
	}
	
	public function checkLogin($s_user, $s_password) {
		try {
			$s_select = $this->_db->select()
						->from('users',array('id'))
						->where('login = "'.$s_user.'"')
						->where('password = md5("'.$s_password.'")');
			$o_rows = $this->_db->fetchAll($s_select);
			return $o_rows[0]['id'];
		}
		catch (Exception $e) {
			error_log("Exception caught in ".__CLASS__."::".__FUNCTION__." on line ".$e->getLine().": ".$e->getMessage());
		}
		return 0;
	}
}
?>