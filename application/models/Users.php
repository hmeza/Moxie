<?php
/**
 * Login and users model.
 */
class Users extends Zend_Db_Table_Abstract {
	protected $_name = 'users';
	protected $_primary = 'id';
	
	public function __construct() {
		$this->_db = Zend_Registry::get('db');
	}
	
	public function checkLogin($s_user, $s_password) {
		try {
			$s_select = $this->_db->select()
						->from('users',array('id','login','language'))
						->where('login = "'.$s_user.'"')
						->where('password = md5("'.$s_password.'")');
			$o_rows = $this->_db->fetchAll($s_select);
			return $o_rows[0];
		}
		catch (Exception $e) {
			error_log("Exception caught in ".__CLASS__."::".__FUNCTION__." on line ".$e->getLine().": ".$e->getMessage());
		}
		return 0;
	}
	
	public function checkKey($s_key) {
		$s_select = $this->_db->select()
			->from("login_keys", array('login'))
			->joinInner("users", "login_keys.login = users.login")
			->where('generated_key = ?', $s_key)
			->where('expiration_date > ?', date('Y-m-d'));
		$st_data = $this->_db->fetchAll($s_select);
		$st_data = $st_data[0];
		if(empty($st_data) || empty($st_data['login']))
			throw new Exception("Invalid key");
		return $st_data;
	}
	
	/**
	 * Generates a key for a login to allow tokenized access.
	 * @param string $s_login
	 * @return string
	 */
	public function generateKey($s_login) {
		$s_key = uniqid();
		$this->_db->insert("login_keys", array(
			'generated_key' => $s_key,
			'login' => $s_login,
			'expiration_date' => date('Y-m-d H:i:s', strtotime('+1 day'))
		));
		return $s_key;
	}
}
?>