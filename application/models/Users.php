<?php
/**
 * Login and users model.
 */
class Users extends Zend_Db_Table_Abstract {
	protected $_name = 'users';
	protected $_primary = 'id';

	public function checkLogin($s_user, $s_password) {
		$result = null;
		try {
			$s_select = $this->select()
						->from('users',array('id','login','language'))
						->where('login = "'.$s_user.'"')
						->where('password = md5("'.$s_password.'")');
			$o_rows = $this->fetchRow($s_select);
			if(!empty($o_rows)) {
				$result = $o_rows->toArray();
			}
		}
		catch (Exception $e) {
			error_log(__METHOD__.", line ".$e->getLine().": ".$e->getMessage());
		}
		return $result;
	}

	public function updateLastLogin($s_user) {
		$s_where = $this->select()
				->where('login = ?', $s_user)
				->getPart(Zend_Db_Select::WHERE);
		$this->update(array('last_login' => new Zend_Db_Expr('NOW()')), $s_where);
	}
	
	public function checkKey($s_key) {
		$s_select = $this->_db->select()
			->from("login_keys", array('login'))
			->joinInner("users", "login_keys.login = users.login")
			->where('generated_key = ?', $s_key)
			->where('expiration_date > ?', date('Y-m-d'));
		$st_data = $this->_db->fetchAll($s_select);
		if(empty($st_data) || empty($st_data[0]['login']))
			throw new Exception("Invalid key");
		$st_data = $st_data[0];
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
 
	/**
	 * @param array $user
	 * @return string
	 */
	public function getValidationKey($user) {
		return sha1($user['login'].$user['password'].$user['email'].$user['created_at']);
	}

	/**
	 * @param string $key
	 * @param array $user
	 * @return bool
	 */
	public function validateKey($key, $user) {
		return $key == $this->getValidationKey($user);
	}

	public function confirm($id) {
		$this->update(array('confirmed' => 1), 'id = '.$id);
		
		// find shared expenses sheets and copy it to new account id
		try {
			$userModel = new Users();
			$user = $userModel->fetchRow('id = '.$id)->toArray();
			$sharedExpensesSheetsUsersModel = new SharedExpensesSheetUsers();
			$sasu = $sharedExpensesSheetsUsersModel
				->fetchAll("email = '".$user['email']."' and id_user is NULL")->toArray();
			foreach($sasu as $s) {
				$sharedExpensesSheetsUsersModel->update(array('id_user' => $id), 'id = '.$s['id']);
			}
		}
		catch(Exception $e) {
			error_log(__METHOD__.", line ".$e->getLine().": ".$e->getMessage());
		}
	}
	
	public function findUserByLogin($username) {
		return $this->_db->fetchRow("select * from users where login = '".$username."'");
	}
	
	public function findUserByEmail($email) {
		return $this->_db->fetchRow("select * from users where email = '".$email."'");
	}
}