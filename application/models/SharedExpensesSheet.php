<?php

class SharedExpensesSheet extends Zend_Db_Table_Abstract {
	private $database;
	protected $_name = 'shared_expenses_sheets';
	protected $_primary = 'id';
	
	public function __construct() {
		global $db;
		$this->database = $db;
		$this->_db = Zend_Registry::get('db');
	}
	
	public function get_by_user_owner($owner_id) {
		return $this->fetchAll("user_owner = ?", $owner_id)->toArray();
	}
	
	public function get_by_user_match($user_id) {
		// return all sheets where the user has participation, including ownership
	}
}
